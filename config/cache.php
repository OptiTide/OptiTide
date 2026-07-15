<?php

return [
    // file (default, persistent) | redis | array (per-request only)
    'driver' => env('CACHE_DRIVER', 'file'),
    'path'   => 'storage/framework/cache',
    'prefix' => env('CACHE_PREFIX', 'cache:'),
];
