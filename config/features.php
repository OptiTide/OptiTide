<?php

/**
 * Feature toggles. Each key switches a whole slice of the product off — the nav
 * link AND the routes behind it (see App\Support\Features).
 *
 * Read these through Features::enabled(), never config() directly: the admin
 * Settings screen writes these keys into the settings table as the STRINGS
 * '1'/'0', which override the booleans below at boot (bin/bootstrap.php), so a
 * raw config() read hands you '0' — a truthy non-empty string.
 *
 * filter_var (not a (bool) cast) is deliberate: it maps 'off'/'no'/'false' to
 * false, whereas (bool) 'off' is true.
 */
return [
    'live_chat'         => filter_var(env('FEATURE_LIVE_CHAT', true), FILTER_VALIDATE_BOOL),
    'ai_chat'           => filter_var(env('FEATURE_AI_CHAT', true), FILTER_VALIDATE_BOOL),
    'careers'           => filter_var(env('FEATURE_CAREERS', true), FILTER_VALIDATE_BOOL),
    'blog'              => filter_var(env('FEATURE_BLOG', true), FILTER_VALIDATE_BOOL),
    'affiliate'         => filter_var(env('FEATURE_AFFILIATE', true), FILTER_VALIDATE_BOOL),
    'api_credits'       => filter_var(env('FEATURE_API_CREDITS', true), FILTER_VALIDATE_BOOL),
    'meetings'          => filter_var(env('FEATURE_MEETINGS', true), FILTER_VALIDATE_BOOL),
    'currency_switcher' => filter_var(env('FEATURE_CURRENCY_SWITCHER', true), FILTER_VALIDATE_BOOL),
    'quotes'            => filter_var(env('FEATURE_QUOTES', true), FILTER_VALIDATE_BOOL),
];
