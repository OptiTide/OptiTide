<?php

namespace App\Services\Whm;

use RuntimeException;

/**
 * Real WHM JSON-API client. Talks to /json-api/listaccts with an API-token
 * header. The WHM host is admin-configured and trusted, so (like the uptime
 * monitor) it deliberately does not go through the SSRF fetcher, and it tolerates
 * WHM's usual self-signed certificate.
 */
final class WhmApiClient implements WhmClient
{
    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $token,
        private string $server,
    ) {}

    public function available(): bool
    {
        return true;
    }

    public function listAccounts(): array
    {
        $url = sprintf('https://%s:%d/json-api/listaccts?api.version=1', $this->host, $this->port);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: whm ' . $this->username . ':' . $this->token],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('WHM request failed: ' . $err);
        }
        if ($status >= 400) {
            throw new RuntimeException('WHM returned HTTP ' . $status);
        }

        $data = json_decode((string) $body, true);
        if (! is_array($data)) {
            throw new RuntimeException('WHM returned an unreadable response.');
        }

        $accounts = $data['data']['acct'] ?? $data['acct'] ?? [];
        $out = [];
        foreach ($accounts as $a) {
            if (empty($a['user'])) {
                continue;
            }
            $out[] = [
                'domain'        => (string) ($a['domain'] ?? ''),
                'user'          => (string) $a['user'],
                'plan'          => isset($a['plan']) ? (string) $a['plan'] : null,
                'status'        => ! empty($a['suspended']) ? 'suspended' : 'active',
                'ip'            => isset($a['ip']) ? (string) $a['ip'] : null,
                'disk_used_mb'  => $this->toMb($a['diskused'] ?? null),
                'disk_limit_mb' => $this->toMb($a['disklimit'] ?? null),
                'server'        => $this->server,
            ];
        }

        return $out;
    }

    public function createCpanelSession(string $username): ?string
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $url = sprintf(
            'https://%s:%d/json-api/create_user_session?api.version=1&user=%s&service=cpaneld',
            $this->host,
            $this->port,
            rawurlencode($username)
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: whm ' . $this->username . ':' . $this->token],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        if ($body === false) {
            return null;
        }

        $data = json_decode((string) $body, true);
        $sessionUrl = $data['data']['url'] ?? null;

        return is_string($sessionUrl) && $sessionUrl !== '' ? $sessionUrl : null;
    }

    public function suspendAccount(string $username, string $reason = ''): bool
    {
        return $this->simpleCall('suspendacct', ['user' => $username, 'reason' => $reason]);
    }

    public function unsuspendAccount(string $username): bool
    {
        return $this->simpleCall('unsuspendacct', ['user' => $username]);
    }

    /** Fire a WHM function and return whether it reported success. */
    private function simpleCall(string $fn, array $params): bool
    {
        $url = sprintf('https://%s:%d/json-api/%s?%s', $this->host, $this->port, $fn, http_build_query(['api.version' => 1] + $params));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: whm ' . $this->username . ':' . $this->token],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false) {
            return false;
        }

        $data = json_decode((string) $body, true);

        return (int) ($data['metadata']['result'] ?? 0) === 1;
    }

    /** Parse WHM disk figures like "512M", "2.5G", "unlimited" into MB. */
    private function toMb(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if (strcasecmp($value, 'unlimited') === 0) {
            return null;
        }

        $num = (float) $value;
        $unit = strtoupper(substr($value, -1));

        return match ($unit) {
            'G'     => (int) round($num * 1024),
            'K'     => (int) round($num / 1024),
            'T'     => (int) round($num * 1024 * 1024),
            default => (int) round($num), // already MB (or "M")
        };
    }
}
