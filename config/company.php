<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supplier identity (for AU tax invoices)
    |--------------------------------------------------------------------------
    |
    | A valid Australian tax invoice must show the supplier's identity and ABN,
    | the words "Tax invoice", the GST amount (or that the total includes GST),
    | and the date. These render on invoices/pdf.blade.php.
    |
    */

    'legal_name' => env('COMPANY_LEGAL_NAME', 'OptiTide Pty Ltd'),

    'abn' => env('COMPANY_ABN'),

    'email' => env('COMPANY_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@optitide.io')),

    'address' => [
        'line1' => env('COMPANY_ADDRESS_LINE1'),
        'locality' => env('COMPANY_ADDRESS_LOCALITY'),   // suburb / city
        'region' => env('COMPANY_ADDRESS_REGION'),       // state, e.g. VIC
        'postcode' => env('COMPANY_ADDRESS_POSTCODE'),
        'country' => env('COMPANY_ADDRESS_COUNTRY', 'Australia'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GST
    |--------------------------------------------------------------------------
    |
    | Australian GST is 10% (1000 basis points). Only a GST-registered supplier
    | (turnover ≥ A$75k) charges GST — set gst_registered=false to bill without
    | it (InvoiceService then computes zero tax and the PDF omits the GST line).
    |
    */

    'gst_registered' => (bool) env('COMPANY_GST_REGISTERED', true),

    'gst_basis_points' => (int) env('COMPANY_GST_BASIS_POINTS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Stripe Tax
    |--------------------------------------------------------------------------
    |
    | When true, Checkout Sessions enable Stripe's automatic_tax so GST is
    | calculated + collected at the point of sale. Requires Stripe Tax to be
    | configured on the account (origin address + registrations), so it is OFF
    | by default — enabling it without that setup errors the checkout.
    |
    | Prices are GST-INCLUSIVE. One-time checkout sets tax_behavior=inclusive on
    | its price_data; for the hosting subscription, set the Stripe Price's Tax
    | behavior to "Inclusive" in the Stripe dashboard, or Stripe will add 10% on
    | top and the charge won't match the issued invoice.
    |
    */

    'stripe_automatic_tax' => (bool) env('STRIPE_AUTOMATIC_TAX', false),

];
