<?php

namespace App\Services;

use App\Enums\MonitorStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\HelpdeskTicket;
use App\Models\ServerMonitor;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Polls admin-configured HTTP endpoints for uptime and TLS-certificate expiry,
 * and auto-opens (then auto-resolves) helpdesk tickets on state changes.
 *
 * Ticket creation is idempotent: the open incident/SSL ticket ids are held on
 * the monitor row and only set while null, under a row lock, so repeated
 * failing checks never spawn duplicate tickets. The monitored URLs are
 * admin-configured and trusted, so the plain Http client is used rather than
 * SafeUrlFetcher (whose private-IP block would reject legitimate internal
 * targets).
 */
class MonitorService
{
    public function __construct(
        private readonly int $failureThreshold = 2,
        private readonly int $sslExpiryDays = 14,
        private readonly int $timeout = 10,
        private readonly int $connectTimeout = 5,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            failureThreshold: (int) config('monitoring.failure_threshold', 2),
            sslExpiryDays: (int) config('monitoring.ssl_expiry_days', 14),
            timeout: (int) config('monitoring.timeout', 10),
            connectTimeout: (int) config('monitoring.connect_timeout', 5),
        );
    }

    /**
     * Run one uptime + SSL check for a monitor and reconcile its ticket state.
     */
    public function check(ServerMonitor $monitor): void
    {
        // Network I/O happens outside the transaction — no locks held while we
        // wait on remote hosts.
        $isUp = $this->checkUptime($monitor);
        $sslExpiresAt = $this->fetchSslExpiry($monitor);

        DB::transaction(function () use ($monitor, $isUp, $sslExpiresAt) {
            // Re-read under a row lock so a concurrent check can't open a second
            // ticket between our null-check and our write.
            /** @var ServerMonitor $fresh */
            $fresh = ServerMonitor::whereKey($monitor->getKey())->lockForUpdate()->first();

            if ($fresh === null) {
                return;
            }

            $fresh->last_checked_at = now();

            // Only overwrite the last-known expiry when we actually read a cert.
            // A transient handshake failure (or a down host) must not wipe a
            // still-valid expiry to null — mirroring reconcileSsl's null-guard.
            if ($sslExpiresAt !== null) {
                $fresh->ssl_expires_at = $sslExpiresAt;
            }

            $isUp
                ? $this->recordUp($fresh)
                : $this->recordDown($fresh);

            $this->reconcileSsl($fresh, $sslExpiresAt);

            $fresh->save();

            // Copy the reconciled state back onto the caller's instance.
            $monitor->setRawAttributes($fresh->getAttributes());
            $monitor->syncOriginal();
        });
    }

    private function recordUp(ServerMonitor $monitor): void
    {
        $monitor->consecutive_failures = 0;

        if ($monitor->status !== MonitorStatus::Up) {
            $monitor->status = MonitorStatus::Up;
            $monitor->last_status_changed_at = now();
        }

        // Auto-resolve the incident ticket on recovery and release the guard.
        if ($monitor->incident_ticket_id !== null) {
            $this->resolveTicket(
                $monitor->incident_ticket_id,
                "Automated monitor recovery: {$monitor->name} ({$monitor->url}) is responding again.",
            );
            $monitor->incident_ticket_id = null;
        }
    }

    private function recordDown(ServerMonitor $monitor): void
    {
        $monitor->consecutive_failures++;

        // Debounce transient blips: only flag Down + page after N failures.
        if ($monitor->consecutive_failures < $this->failureThreshold) {
            return;
        }

        if ($monitor->status !== MonitorStatus::Down) {
            $monitor->status = MonitorStatus::Down;
            $monitor->last_status_changed_at = now();
        }

        // Idempotent open: only when no incident ticket is currently tracked.
        if ($monitor->incident_ticket_id === null) {
            $ticket = $this->openTicket(
                "[Monitor] {$monitor->name} is DOWN",
                "Automated alert: {$monitor->name} ({$monitor->url}) has failed "
                    ."{$monitor->consecutive_failures} consecutive uptime checks.",
                TicketPriority::Urgent,
            );

            $monitor->incident_ticket_id = $ticket?->id;
        }
    }

    private function reconcileSsl(ServerMonitor $monitor, ?Carbon $expiresAt): void
    {
        // Cannot determine expiry (non-HTTPS or handshake failed) — leave any
        // existing SSL ticket alone rather than false-resolving it.
        if ($expiresAt === null) {
            return;
        }

        $expiringSoon = $expiresAt->lessThanOrEqualTo(now()->addDays($this->sslExpiryDays));

        if ($expiringSoon && $monitor->ssl_ticket_id === null) {
            $days = (int) now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);
            $when = $days < 0
                ? 'expired '.abs($days).' day(s) ago'
                : "expires in {$days} day(s)";

            $ticket = $this->openTicket(
                "[Monitor] TLS certificate for {$monitor->name} {$when}",
                "Automated alert: the TLS certificate for {$monitor->name} ({$monitor->url}) "
                    ."{$when} (on {$expiresAt->toDayDateTimeString()}). Renew it before it lapses.",
                TicketPriority::High,
            );

            $monitor->ssl_ticket_id = $ticket?->id;
        } elseif (! $expiringSoon && $monitor->ssl_ticket_id !== null) {
            // Certificate was renewed — resolve and release the guard.
            $this->resolveTicket(
                $monitor->ssl_ticket_id,
                "Automated resolution: the TLS certificate for {$monitor->name} is now valid until "
                    .$expiresAt->toDayDateTimeString().'.',
            );
            $monitor->ssl_ticket_id = null;
        }
    }

    private function openTicket(string $subject, string $body, TicketPriority $priority): ?HelpdeskTicket
    {
        $owner = $this->systemOwner();

        if ($owner === null) {
            Log::warning('MonitorService: no admin user to own auto-opened ticket; skipping.', [
                'subject' => $subject,
            ]);

            return null;
        }

        $ticket = HelpdeskTicket::create([
            'user_id' => $owner->id,
            'subject' => $subject,
            'status' => TicketStatus::Open,
            'priority' => $priority,
        ]);

        $ticket->messages()->create([
            'user_id' => $owner->id,
            'body' => $body,
            'is_internal' => true, // infra alerts never surface to a client
        ]);

        return $ticket;
    }

    private function resolveTicket(int $ticketId, string $note): void
    {
        $ticket = HelpdeskTicket::find($ticketId);

        if ($ticket === null || $ticket->status === TicketStatus::Resolved || $ticket->status === TicketStatus::Closed) {
            return;
        }

        $ticket->messages()->create([
            'user_id' => $ticket->user_id,
            'body' => $note,
            'is_internal' => true,
        ]);

        $ticket->update([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    private function systemOwner(): ?User
    {
        return User::where('role', UserRole::Admin)->orderBy('id')->first();
    }

    protected function checkUptime(ServerMonitor $monitor): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders(['User-Agent' => 'OptiTide-Monitor/1.0'])
                ->get($monitor->url);

            // Any 2xx/3xx is healthy; 4xx/5xx (and connection errors below) are down.
            return $response->status() < 400;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Fetch the TLS certificate's expiry via a raw socket. verify_peer is off
     * so we still capture an already-expired/invalid cert (exactly when we want
     * to alert). Returns null for non-HTTPS URLs or handshake failures.
     */
    protected function fetchSslExpiry(ServerMonitor $monitor): ?Carbon
    {
        $host = $monitor->host();

        if ($host === null || ! str_starts_with(strtolower($monitor->url), 'https://')) {
            return null;
        }

        $port = parse_url($monitor->url, PHP_URL_PORT) ?: 443;

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'SNI_enabled' => true,
                    'peer_name' => $host,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $this->connectTimeout,
                STREAM_CLIENT_CONNECT,
                $context,
            );

            if ($client === false) {
                return null;
            }

            try {
                $params = stream_context_get_params($client);
            } finally {
                // Always release the socket, even if param extraction throws.
                fclose($client);
            }

            $cert = $params['options']['ssl']['peer_certificate'] ?? null;

            if ($cert === null) {
                return null;
            }

            $parsed = openssl_x509_parse($cert);

            if (! is_array($parsed) || ! isset($parsed['validTo_time_t'])) {
                return null;
            }

            return Carbon::createFromTimestamp($parsed['validTo_time_t']);
        } catch (Throwable) {
            return null;
        }
    }
}
