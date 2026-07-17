<?php

/*
|----------------------------------------------------------------------------
| Payment gateways
|----------------------------------------------------------------------------
| Each gateway implements App\Services\Payments\PaymentGateway and is
| registered in App\Services\Payments\PaymentManager. Adding a new provider
| (Stripe, PayTo via Monoova/Zepto, GoCardless, …) is a matter of dropping in
| a class and listing it here — the billing core never changes.
|
| Neither PayID nor Payoneer payment links give us a reliable automatic
| settlement webhook in this configuration, so invoices are reconciled by
| staff recording a payment. A webhook endpoint can be added later without
| touching the invoice model (see PaymentManager::handleWebhook()).
*/

return [
    // Order shown to the client on the pay page. First = default.
    // PayID first, then Skrill, then the rest. First = preferred.
    'enabled' => array_values(array_filter([
        env('PAYID_ENABLED', true) ? 'payid' : null,
        env('SKRILL_ENABLED', true) ? 'skrill' : null,
        env('PAYPAL_ENABLED', true) ? 'paypal' : null,
        env('PAYONEER_ENABLED', true) ? 'payoneer' : null,
    ])),

    'gateways' => [
        'payid' => [
            'label'        => 'PayID / Bank transfer',
            'type'         => env('PAYID_TYPE', 'mobile'),   // mobile | email | abn
            'value'        => env('PAYID_VALUE', ''),
            'account_name' => env('PAYID_ACCOUNT_NAME', env('COMPANY_LEGAL_NAME', 'OptiTide')),
            'bsb'          => env('BANK_BSB', ''),
            'account_number' => env('BANK_ACCOUNT_NUMBER', ''),
        ],

        'skrill' => [
            'label'          => 'Skrill',
            'merchant_email' => env('SKRILL_MERCHANT_EMAIL', ''),
        ],

        'paypal' => [
            'label'     => 'PayPal',
            'me_handle' => env('PAYPAL_ME', ''),   // your PayPal.Me handle (without the URL)
        ],

        'payoneer' => [
            'label'      => 'Payoneer',
            'mode'       => env('PAYONEER_MODE', 'manual'),  // manual | api
            'api_key'    => env('PAYONEER_API_KEY', ''),
            'program_id' => env('PAYONEER_PROGRAM_ID', ''),
        ],
    ],
];
