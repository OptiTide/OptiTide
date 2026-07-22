<?php

/*
|----------------------------------------------------------------------------
| Domain registrar (Spaceship) — availability lookup.
|----------------------------------------------------------------------------
| Credentials come from Spaceship's API Manager (Launchpad > API Manager >
| "+ New API Key"). The key needs the `domains:read` scope for availability.
|
| Put the real key and secret in .env or the host's environment panel — never
| in this file, which IS committed.
|
| NO SANDBOX EXISTS. Spaceship documents no test environment, so anything
| beyond a read-only availability check runs against live data with real money
| attached. That is why this config only carries availability for now.
|
| PRICING IS NOT AVAILABLE FROM THIS API. Every operation in the published
| OpenAPI spec was enumerated and there is no price-list endpoint — the only
| price data returned anywhere is `premiumPricing` on an availability result,
| which appears solely for premium (aftermarket) names. Retail pricing
| therefore has to come from the catalogue you control, which is what a
| reseller wants regardless, since you set your own margin.
*/

return [
    'driver' => env('DOMAIN_REGISTRAR', 'spaceship'),

    'spaceship' => [
        'base_url'   => env('SPACESHIP_BASE_URL', 'https://spaceship.dev/api/v1'),
        'api_key'    => env('SPACESHIP_API_KEY', ''),
        'api_secret' => env('SPACESHIP_API_SECRET', ''),
        'timeout'    => (int) env('SPACESHIP_TIMEOUT', 15),
    ],

    /*
    | TLDs offered in the domain search, in the order shown.
    |
    | Kept short deliberately. Every extra TLD is another entry in the batch
    | (capped at 20 by the API) and the availability endpoint allows only 30
    | calls per user per 30 seconds — a 20-TLD suggestion list would let a
    | handful of impatient visitors exhaust the whole account's quota.
    */
    'tlds' => ['com.au', 'au', 'com', 'net.au', 'org.au', 'net', 'org', 'io'],
];
