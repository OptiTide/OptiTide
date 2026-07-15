<?php

/**
 * Display currencies for the storefront. Prices are STORED in AUD (the base /
 * settlement currency — invoices, GST and the payment gateways are all AUD).
 * A visitor can switch the displayed currency; USD figures are indicative and
 * converted from AUD at the rate below. To offer true USD settlement later,
 * wire a live FX feed and convert at invoice-creation time.
 */
return [
    'base'      => 'AUD',
    'default'   => env('DISPLAY_CURRENCY_DEFAULT', 'AUD'),
    'supported' => ['AUD', 'USD'],

    'symbols' => [
        'AUD' => 'A$',
        'USD' => 'US$',
    ],

    // Multipliers from the AUD base. AUD is always 1.0.
    'rates' => [
        'AUD' => 1.0,
        'USD' => (float) env('FX_AUD_TO_USD', 0.66),
    ],
];
