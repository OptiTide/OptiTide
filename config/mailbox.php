<?php

/**
 * Inbound email → helpdesk.
 *
 * Two ways in, both config-gated:
 *  1. Webhook (recommended, works on any server): forward your Proton inbox to an
 *     inbound-parse service (Mailgun/Postmark/CloudMailin) that POSTs each email
 *     to /webhooks/email?token=WEBHOOK_SECRET. No IMAP needed.
 *  2. IMAP pull (tickets:import-email): for a mailbox exposed over IMAP — e.g.
 *     Proton Bridge, or any IMAP host — when the PHP imap extension is present.
 */
return [
    'webhook_secret' => env('EMAIL_WEBHOOK_SECRET', ''),

    // IMAP pull (optional)
    'host'     => env('IMAP_HOST', ''),
    'port'     => (int) env('IMAP_PORT', 993),
    'ssl'      => (bool) env('IMAP_SSL', true),
    'username' => env('IMAP_USERNAME', ''),
    'password' => env('IMAP_PASSWORD', ''),
    'folder'   => env('IMAP_FOLDER', 'INBOX'),
];
