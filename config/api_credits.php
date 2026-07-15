<?php

/**
 * OptiTide API — a white-labelled resale of Claude. Clients buy prepaid credit
 * and call our endpoint with an "optitide-*" model alias; they never see the
 * upstream provider. Requires ANTHROPIC_API_KEY (config/ai.php) to actually
 * serve completions — otherwise the endpoint fails closed.
 */
return [
    'enabled' => filter_var(env('API_CREDITS_ENABLED', true), FILTER_VALIDATE_BOOL),

    // White-label model aliases → real upstream models (never exposed to clients).
    'models' => [
        'optitide-lite' => env('API_MODEL_LITE', 'claude-haiku-4-5-20251001'),
        'optitide-pro'  => env('API_MODEL_PRO', 'claude-sonnet-5'),
    ],
    'default_model' => 'optitide-lite',

    // Billed price per 1,000,000 tokens, in cents (AUD) — markup already baked in.
    'pricing' => [
        'optitide-lite' => ['in' => 150, 'out' => 750],
        'optitide-pro'  => ['in' => 600, 'out' => 3000],
    ],

    // Hard cap on max_tokens per request (bounds the worst-case charge).
    'max_tokens_cap' => (int) env('API_MAX_TOKENS_CAP', 4096),

    // Credit top-up packs offered in the portal (cents).
    'packs' => [2500, 5000, 10000, 25000],
    'min_topup_cents' => 1000,
    'max_topup_cents' => 500000,
];
