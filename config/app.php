<?php

return [
    'name'     => env('APP_NAME', 'OptiTide'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => rtrim(env('APP_URL', 'http://localhost:8000'), '/'),
    'key'      => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'Australia/Sydney'),

    'brand' => [
        'accent'      => env('BRAND_ACCENT', '#0d9488'),
        'accent_dark' => env('BRAND_ACCENT_DARK', '#0f766e'),
    ],
];
