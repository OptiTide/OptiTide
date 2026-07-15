<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Services\Audit\AuditLog;
use App\Support\Money;

/**
 * Late fees on overdue invoices.
 *
 * The fee is a GST-free penalty equal to a configured percentage of the balance
 * due, applied exactly once when an invoice tips into "overdue" and folded into
 * total_cents (so balance = total − paid stays correct everywhere). It never
 * compounds: once late_fee_cents is set (or a waiver is approved) it won't apply
 * again to that invoice.
 *
 * Waiver flow: staff can request a waiver (fee stays until approved); only an
 * admin can approve it (removing the fee). "waived" is terminal.
 */
final class LateFeeService
{
    public const WAIVER_NONE = 'none';
    public const WAIVER_REQUESTED = 'requested';
    public const WAIVER_WAIVED = 'waived';

    /** Compute the fee an invoice would attract right now (0 if none/ineligible). */
    public function computeFor(array $invoice): int
    {
        if (! config('billing.late_fee_enabled')) {
            return 0;
        }
        $balance = Invoice::balance($invoice);
        if (! $balance->isPositive()) {
            return 0;
        }

        $percent = (float) config('billing.late_fee_percent', 0);
        $fee = (int) round($balance->minorUnits * $percent / 100);

        $min = (int) config('billing.late_fee_min_cents', 0);
        if ($min > 0 && $fee < $min) {
            $fee = $min;
        }

        return max(0, $fee);
    }

    /**
     * Apply the late fee once, at the moment an invoice becomes overdue.
     * Returns the fee applied in cents, or 0 if nothing was charged.
     */
    public function applyOnOverdue(array $invoice): int
    {
        // Never re-apply: a fee already exists, or a waiver was already approved.
        if ((int) ($invoice['late_fee_cents'] ?? 0) > 0) {
            return 0;
        }
        if (($invoice['late_fee_waiver_status'] ?? self::WAIVER_NONE) === self::WAIVER_WAIVED) {
            return 0;
        }

        $fee = $this->computeFor($invoice);
        if ($fee <= 0) {
            return 0;
        }

        Invoice::updateById($invoice['id'], [
            'late_fee_cents' => $fee,
            'total_cents'    => (int) $invoice['total_cents'] + $fee,
            'updated_at'     => now(),
        ]);

        AuditLog::record('invoice.late_fee_applied', 'invoice', $invoice['id'], [
            'fee' => (new Money($fee, $invoice['currency'] ?? 'AUD'))->format(),
        ], null); // system action (scheduler) — actor resolves to System

        return $fee;
    }

    /** Staff request that the fee be waived (pending an admin's approval). */
    public function requestWaiver(int|string $invoiceId, ?int $actorId = null): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if ((int) $invoice['late_fee_cents'] <= 0) {
            return;
        }
        if (($invoice['late_fee_waiver_status'] ?? self::WAIVER_NONE) !== self::WAIVER_NONE) {
            return; // already requested or waived
        }

        Invoice::updateById($invoiceId, ['late_fee_waiver_status' => self::WAIVER_REQUESTED]);
        AuditLog::record('invoice.late_fee_waiver_requested', 'invoice', $invoiceId);
    }

    /** Admin approves the waiver: reverse the fee and close it out. */
    public function waive(int|string $invoiceId, ?string $reason = null): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $fee = (int) $invoice['late_fee_cents'];

        Invoice::updateById($invoiceId, [
            'late_fee_cents'         => 0,
            'total_cents'            => max(0, (int) $invoice['total_cents'] - $fee),
            'late_fee_waiver_status' => self::WAIVER_WAIVED,
            'updated_at'             => now(),
        ]);

        AuditLog::record('invoice.late_fee_waived', 'invoice', $invoiceId, [
            'reversed' => (new Money($fee, $invoice['currency'] ?? 'AUD'))->format(),
            'reason'   => $reason ?: null,
        ]);
    }
}
