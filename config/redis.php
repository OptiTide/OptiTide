<?php

/*
|----------------------------------------------------------------------------
| Redis — used for sessions, cache and rate limiting when enabled.
|----------------------------------------------------------------------------
| Uses the pure-PHP predis client (no phpredis extension required). Set
| REDIS_URL (redis://[:password@]host:port[/db]) for managed Redis, or the
| discrete REDIS_HOST/PORT/PASSWORD/DB values.
*/

return [
    'url'      => env('REDIS_URL'),
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'port'     => (int) env('REDIS_PORT', 6379),
    'password' => env('REDIS_PASSWORD') ?: null,
    'database' => (int) env('REDIS_DB', 0),
    'prefix'   => env('REDIS_PREFIX', 'optitide:'),
];
