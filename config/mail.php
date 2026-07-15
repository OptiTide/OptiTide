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
];
