<?php

/**
 * WHM/cPanel reseller connection. Real syncing is only enabled when host,
 * username and an API token are all set (see WhmClientFactory) — otherwise the
 * fail-closed NullWhmClient is used and syncing is inert. Credentials live in
 * .env, never in the database.
 */
return [
    'host'         => env('WHM_HOST', ''),
    'port'         => (int) env('WHM_PORT', 2087),
    'username'     => env('WHM_USERNAME', ''),
    'api_token'    => env('WHM_API_TOKEN', ''),
    'server_label' => env('WHM_SERVER_LABEL', 'Primary Server'),
];
