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
    | SMTP (MAIL_DRIVER=smtp).
    |
    | encryption: 'tls' = STARTTLS, the normal choice on port 587.
    |             'ssl' = implicit TLS, port 465.
    | Anything else is refused rather than sending the password in the clear.
    |
    | The password belongs in .env (gitignored) or the host's environment
    | panel — never in this file, which IS committed.
    */
    'smtp' => [
        'host'       => env('SMTP_HOST', ''),
        'port'       => (int) env('SMTP_PORT', 587),
        'username'   => env('SMTP_USERNAME', ''),
        'password'   => env('SMTP_PASSWORD', ''),
        'encryption' => env('SMTP_ENCRYPTION', 'tls'),
        'timeout'    => (int) env('SMTP_TIMEOUT', 20),
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
