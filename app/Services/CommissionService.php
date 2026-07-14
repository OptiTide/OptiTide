<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Models\Commission;

/**
 * Guarded commission status transitions. The lifecycle is
 * pending → approved → (credited | paid); every step is a no-op unless the
 * current status permits it, so a double-click or a stale action can't
 * mis-transition money.
 */
class CommissionService
{
    /** Admin verifies the commission is owed (pending → approved). */
    public function approve(Commission $commission): void
    {
        if ($commission->status !== CommissionStatus::Pending) {
            return;
        }

        $commission->forceFill([
            'status' => CommissionStatus::Approved,
            'approved_at' => now(),
        ])->save();
    }

    /** Referrer takes it as account credit (approved → credited). */
    public function applyAsCredit(Commission $commission): void
    {
        if ($commission->status !== CommissionStatus::Approved) {
            return;
        }

        $commission->forceFill([
            'status' => CommissionStatus::Credited,
            'settled_at' => now(),
        ])->save();
    }

    /**
     * Admin records a cash payout (approved → paid). Credited is a terminal
     * state — a commission taken as account credit can't also be paid out in
     * cash (no double-settlement).
     */
    public function markPaid(Commission $commission): void
    {
        if ($commission->status !== CommissionStatus::Approved) {
            return;
        }

        $commission->forceFill([
            'status' => CommissionStatus::Paid,
            'settled_at' => now(),
        ])->save();
    }
}
