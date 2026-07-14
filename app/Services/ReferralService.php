<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\ReferralRelationship;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns the affiliate flow: linking a referred user to their referrer at
 * registration, and creating the referrer's commission on the referred user's
 * FIRST paid order. Both operations are idempotent.
 */
class ReferralService
{
    /**
     * Link $referred to the referrer who owns $code (from the ?ref cookie).
     * No-ops on a blank/unknown code, a self-referral, or a user already
     * attributed.
     */
    public function attachReferral(User $referred, ?string $code): void
    {
        if (blank($code) || $referred->referred_by !== null) {
            return;
        }

        $referrer = User::where('referral_code', $code)->first();

        // No referrer, self-referral, or a staff referrer (staff must not earn
        // commissions on client signups — segregation of duties).
        if ($referrer === null || $referrer->id === $referred->id || $referrer->isStaff()) {
            return;
        }

        DB::transaction(function () use ($referred, $referrer, $code) {
            // One relationship per referred user (referred_id is unique).
            if (ReferralRelationship::where('referred_id', $referred->id)->exists()) {
                return;
            }

            $referred->forceFill(['referred_by' => $referrer->id])->save();

            ReferralRelationship::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'referral_code' => $code,
            ]);
        });
    }

    /**
     * Create the referrer's commission when a referred user's FIRST order is
     * paid. Called from inside the Stripe webhook's once-only fulfilment block;
     * the compare-and-swap on `converted_at` makes it fire at most once per
     * referred user, so a second order (or webhook redelivery) earns nothing.
     */
    public function recordCommissionForFirstPaidOrder(Order $order): void
    {
        $referrerId = $order->user?->referred_by;

        if ($referrerId === null) {
            return;
        }

        $claimed = ReferralRelationship::where('referred_id', $order->user_id)
            ->whereNull('converted_at')
            ->update(['converted_at' => now()]);

        if ($claimed === 0) {
            return; // already converted — not the first paid order
        }

        $bps = (int) config('affiliate.commission_basis_points');
        $amount = $order->total->percentage($bps);

        Commission::create([
            'referrer_id' => $referrerId,
            'order_id' => $order->id,
            'amount' => $amount->amount,
            'currency' => $amount->currency,
            'rate_basis_points' => $bps,
            'status' => CommissionStatus::Pending,
        ]);
    }
}
