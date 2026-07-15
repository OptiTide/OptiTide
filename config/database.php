<?php

// Managed Postgres (Coolify, Railway, Heroku-style) usually exposes a single
// connection URL. Parse it so DATABASE_URL alone is enough to configure pgsql.
$databaseUrl = env('DATABASE_URL');
$pg = [];
if ($databaseUrl && ($parts = parse_url($databaseUrl))) {
    $pg = [
        'host'     => $parts['host'] ?? '127.0.0.1',
        'port'     => $parts['port'] ?? 5432,
        'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : '',
        'username' => isset($parts['user']) ? urldecode($parts['user']) : '',
        'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
    ];
}

return [
    // A DATABASE_URL implies Postgres unless DB_CONNECTION says otherwise.
    'default' => env('DB_CONNECTION', $databaseUrl ? 'pgsql' : 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', 'database/optitide.sqlite'),
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => $pg['host'] ?? env('DB_HOST', '127.0.0.1'),
            'port'     => (int) ($pg['port'] ?? env('DB_PORT', 5432)),
            'database' => $pg['database'] ?? env('DB_DATABASE', 'optitide'),
            'username' => $pg['username'] ?? env('DB_USERNAME', 'optitide'),
            'password' => $pg['password'] ?? env('DB_PASSWORD', ''),
            'schema'   => env('DB_SCHEMA', 'public'),
            'sslmode'  => env('DB_SSLMODE'),
        ],

        'mysql' => [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'optitide'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
        ],
    ],
];
