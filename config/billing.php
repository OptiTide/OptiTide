<?php

/**
 * Billing lifecycle policy. Auto-suspend puts a client on hold once an invoice
 * has been overdue past the grace period; paying it clears the hold.
 */
return [
    // Days past the due date before a client is auto-suspended.
    'suspend_after_days' => (int) env('AUTO_SUSPEND_DAYS', 30),

    // Late fees — applied once when an invoice tips into "overdue". The fee is a
    // GST-free penalty (not consideration for a supply under AU GST rules) equal
    // to a percentage of the outstanding balance, with an optional floor. An
    // admin can waive it (staff can request a waiver; only an admin approves).
    'late_fee_enabled'   => filter_var(env('LATE_FEE_ENABLED', true), FILTER_VALIDATE_BOOL),
    'late_fee_percent'   => (float) env('LATE_FEE_PERCENT', 5),   // % of the balance due
    'late_fee_min_cents' => (int) env('LATE_FEE_MIN_CENTS', 0),   // optional minimum fee
];
