<?php

return [
    'name'     => env('APP_NAME', 'OptiTide'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => rtrim(env('APP_URL', 'http://localhost:8000'), '/'),
    'key'      => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'Australia/Perth'),

    'brand' => [
        'accent'      => env('BRAND_ACCENT', '#FF6A00'),
        'accent_dark' => env('BRAND_ACCENT_DARK', '#E85F00'),
    ],
];
