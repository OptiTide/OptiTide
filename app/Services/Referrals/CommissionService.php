<?php

namespace App\Services\Referrals;

use App\Models\Commission;
use App\Models\Referral;
use App\Models\User;
use App\Support\Money;

final class CommissionService
{
    /**
     * Create a pending commission for the referrer of a paid invoice's client —
     * once per client (their first paid invoice). Self-guards against duplicates
     * via the client check + the unique invoice_id column, so it's safe to call
     * on every payment.
     */
    public function recordForPaidInvoice(array $invoice): ?array
    {
        $clientId = $invoice['client_id'] ?? null;
        if (! $clientId) {
            return null;
        }

        // One acquisition commission per referred client (first paid invoice).
        if (Commission::firstWhere('client_id', $clientId)) {
            return null;
        }

        $user = User::query()->where('client_id', $clientId)->where('role', User::ROLE_CLIENT)->first();
        if (! $user) {
            return null;
        }

        $referral = Referral::firstWhere('referred_id', $user['id']);
        if (! $referral) {
            return null;
        }

        $rate = max(0, (int) config('affiliate.commission_bps', 1000));
        $amountCents = (int) round((int) $invoice['total_cents'] * $rate / 10000);
        if ($amountCents <= 0) {
            return null;
        }

        try {
            return Commission::create([
                'referrer_id'  => $referral['referrer_id'],
                'client_id'    => $clientId,
                'invoice_id'   => $invoice['id'],
                'amount_cents' => $amountCents,
                'currency'     => $invoice['currency'],
                'rate_bps'     => $rate,
                'status'       => Commission::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            // Unique(invoice_id) race — a commission already exists. Fine.
            return null;
        }
    }

    public function approve(int|string $id): void
    {
        $c = Commission::find($id);
        if ($c && $c['status'] === Commission::STATUS_PENDING) {
            Commission::updateById($id, ['status' => Commission::STATUS_APPROVED]);
        }
    }

    public function markPaid(int|string $id): void
    {
        $c = Commission::find($id);
        if ($c && in_array($c['status'], [Commission::STATUS_PENDING, Commission::STATUS_APPROVED], true)) {
            Commission::updateById($id, ['status' => Commission::STATUS_PAID]);
        }
    }

    /** @return array{pending:Money,approved:Money,paid:Money,count:int} */
    public function summary(int|string $referrerId, string $currency = 'AUD'): array
    {
        $totals = ['pending' => 0, 'approved' => 0, 'paid' => 0];
        $rows = Commission::query()->where('referrer_id', $referrerId)->get();
        foreach ($rows as $c) {
            if (isset($totals[$c['status']])) {
                $totals[$c['status']] += (int) $c['amount_cents'];
            }
        }

        return [
            'pending'  => new Money($totals['pending'], $currency),
            'approved' => new Money($totals['approved'], $currency),
            'paid'     => new Money($totals['paid'], $currency),
            'count'    => count($rows),
        ];
    }
}
