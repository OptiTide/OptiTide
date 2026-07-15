<?php

return [
    // file (default) | redis
    'driver'   => env('SESSION_DRIVER', 'file'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120), // minutes
    'cookie'   => env('SESSION_COOKIE', 'optitide_session'),
];
