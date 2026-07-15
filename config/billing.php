<?php

/**
 * Billing lifecycle policy. Auto-suspend puts a client on hold once an invoice
 * has been overdue past the grace period; paying it clears the hold.
 */
return [
    // Days past the due date before a client is auto-suspended.
    'suspend_after_days' => (int) env('AUTO_SUSPEND_DAYS', 30),
];
