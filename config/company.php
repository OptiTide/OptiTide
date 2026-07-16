<?php

/*
|----------------------------------------------------------------------------
| Company identity — required for valid Australian tax invoices.
|----------------------------------------------------------------------------
| A valid AU tax invoice must show the supplier's identity + ABN, the words
| "Tax invoice", the date, and the GST amount (or that the total includes
| GST). These values render on the invoice PDF and public pay page.
|
| GST is treated as INCLUSIVE: AU prices already include 10% GST, so the GST
| component is backed out of the total (total / 11), never added on top.
*/

return [
    'legal_name' => env('COMPANY_LEGAL_NAME') ?: 'OptiTide Pty Ltd',
    'abn'        => env('COMPANY_ABN') ?: '38 163 865 712',
    'email'      => env('COMPANY_EMAIL', env('MAIL_FROM_ADDRESS', 'Hello@OptiTide.io')),
    'phone'      => env('COMPANY_PHONE'),

    'address' => [
        'line1'    => env('COMPANY_ADDRESS_LINE1'),
        'locality' => env('COMPANY_ADDRESS_LOCALITY'),
        'region'   => env('COMPANY_ADDRESS_REGION'),
        'postcode' => env('COMPANY_ADDRESS_POSTCODE'),
        'country'  => env('COMPANY_ADDRESS_COUNTRY', 'Australia'),
    ],

    // Shown in the top bar, footer and contact pages. Editable in admin Settings.
    'hours' => env('COMPANY_HOURS') ?: 'Mon – Fri, 9am – 5pm AEST',

    // Footer social links — each is hidden entirely when left blank.
    'social' => [
        'facebook'  => env('COMPANY_SOCIAL_FACEBOOK'),
        'instagram' => env('COMPANY_SOCIAL_INSTAGRAM'),
        'linkedin'  => env('COMPANY_SOCIAL_LINKEDIN'),
    ],

    'currency'         => env('CURRENCY', 'AUD'),
    'gst_registered'   => (bool) env('COMPANY_GST_REGISTERED', true),
    'gst_basis_points' => (int) env('COMPANY_GST_BASIS_POINTS', 1000),
];
