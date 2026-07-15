<?php

/**
 * Referral / affiliate program. A referrer earns a commission (percentage of the
 * order total) on the FIRST paid invoice of a client they referred.
 */
return [
    // Commission rate in basis points (1000 = 10%).
    'commission_bps' => (int) env('AFFILIATE_COMMISSION_BPS', 1000),

    // How long the first-touch referral cookie lasts.
    'cookie_days' => (int) env('AFFILIATE_COOKIE_DAYS', 60),

    'cookie_name' => 'ot_ref',
];
