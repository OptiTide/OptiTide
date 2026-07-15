<?php

namespace App\Services\Api;

use App\Core\Database;
use App\Models\ApiCreditTransaction;
use App\Models\Client;
use App\Services\Audit\AuditLog;

/**
 * Prepaid API-credit wallet, one per client. Every movement is written to the
 * append-only api_credit_transactions ledger with the resulting balance. Reads
 * and writes happen inside a transaction (SQLite serialises writers; Postgres
 * gets the same isolation via the surrounding transaction) so concurrent charges
 * can't oversell.
 */
final class ApiCreditService
{
    public function balance(int|string $clientId): int
    {
        $client = Client::find($clientId);

        return (int) ($client['api_credit_cents'] ?? 0);
    }

    public function hasCredit(int|string $clientId): bool
    {
        return $this->balance($clientId) > 0;
    }

    /**
     * Apply a signed delta and log it. If $reference is given and a row with that
     * reference already exists, this is a no-op (idempotent top-ups on payment).
     * $allowNegative lets a final usage charge overshoot to a small negative
     * balance (bounded by the per-request token cap) rather than be given away free.
     *
     * @return int the resulting balance in cents
     */
    public function record(
        int|string $clientId,
        int $deltaCents,
        string $type,
        ?string $description = null,
        ?string $reference = null,
        array $meta = [],
        bool $allowNegative = false,
    ): int {
        return Database::instance()->transaction(function () use ($clientId, $deltaCents, $type, $description, $reference, $meta, $allowNegative) {
            if ($reference !== null && ApiCreditTransaction::query()->where('reference', $reference)->first()) {
                return $this->balance($clientId); // already applied
            }

            $client = Client::findOrFail($clientId);
            $current = (int) ($client['api_credit_cents'] ?? 0);
            $new = $current + $deltaCents;
            if ($new < 0 && ! $allowNegative) {
                $new = $current; // reject: would overdraw and we weren't told to allow it
                $deltaCents = 0;
            }

            Client::updateById($clientId, ['api_credit_cents' => $new]);
            ApiCreditTransaction::create([
                'client_id'           => $clientId,
                'delta_cents'         => $deltaCents,
                'balance_after_cents' => $new,
                'type'                => $type,
                'description'         => $description,
                'reference'           => $reference,
                'meta'                => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'created_at'          => now(),
            ]);

            return $new;
        });
    }

    /** Grant credit (purchase, refund or admin adjustment). */
    public function topUp(int|string $clientId, int $cents, string $description, ?string $reference = null): int
    {
        $balance = $this->record($clientId, abs($cents), 'topup', $description, $reference);
        AuditLog::record('api_credit.topup', 'client', $clientId, ['amount_cents' => abs($cents), 'reference' => $reference]);

        return $balance;
    }

    /**
     * Charge for usage AFTER a successful upstream call. Always settles (never
     * gives the completion away free); the request gate already required a
     * positive balance, and the token cap bounds any overshoot.
     */
    public function settleUsage(int|string $clientId, int $cents, string $description, array $meta = []): int
    {
        return $this->record($clientId, -abs($cents), 'usage', $description, null, $meta, allowNegative: true);
    }

    /** Admin manual adjustment (may be positive or negative, never below zero). */
    public function adjust(int|string $clientId, int $cents, ?string $reason, int|string|null $actorId = null): int
    {
        $balance = $this->record($clientId, $cents, 'adjust', $reason);
        AuditLog::record('api_credit.adjusted', 'client', $clientId, ['amount_cents' => $cents, 'reason' => $reason]);

        return $balance;
    }

    /** @return array<int,array> recent ledger rows, newest first */
    public function ledger(int|string $clientId, int $limit = 50): array
    {
        return ApiCreditTransaction::query()
            ->where('client_id', $clientId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
