<?php

/**
 * Marketing / tracking integrations. These are IDs only — never raw <script>
 * tags. The public <x-analytics> partial re-validates each ID and emits a fixed
 * official snippet, so a malformed or hostile value can never inject markup.
 *
 * All four are editable online (Admin → Settings) and stored as DB settings with
 * these exact dot-path keys, which override the env defaults at boot.
 */
return [
    // Google Analytics 4 Measurement ID, e.g. G-XXXXXXXXXX
    'ga4' => env('GA4_ID', ''),

    // Google Tag Manager container ID, e.g. GTM-XXXXXXX
    'gtm' => env('GTM_ID', ''),

    // Google Search Console verification token (the content="" value)
    'gsc' => env('GSC_VERIFICATION', ''),

    // Meta (Facebook) Pixel ID — numeric
    'meta_pixel' => env('META_PIXEL_ID', ''),
];
