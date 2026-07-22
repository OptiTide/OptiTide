<?php

return [
    'driver' => env('MAIL_DRIVER', 'resend'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'Hello@OptiTide.io'),
        'name'    => env('MAIL_FROM_NAME', env('APP_NAME', 'OptiTide')),
    ],

    'reply_to' => env('MAIL_REPLY_TO', env('MAIL_FROM_ADDRESS', 'Hello@OptiTide.io')),

    'resend' => [
        'api_key'  => env('RESEND_API_KEY', ''),
        'endpoint' => 'https://api.resend.com/emails',
    ],

    /*
    | Record every send in the email_logs table (Admin > Email Log).
    |
    | ON by default and it should stay on — it is the only record of what the
    | system actually sent a client, and the first thing you want when someone
    | says an invoice never arrived. Bodies are stored with reset/verify tokens
    | redacted, and are cleared by `emails:prune` on the daily schedule.
    */
    'log_to_db' => (bool) env('MAIL_LOG_TO_DB', true),
];
