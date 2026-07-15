<?php

/**
 * Payment-plan options per service line, offered at order time. Each plan splits
 * the price into one or more invoices (percentage + how many days after ordering
 * it's due). A 'months' multiplier bills several periods up front (hosting yearly).
 * The first plan listed is the default. Lines with no entry (SMM) get pay-in-full
 * only.
 */
return [
    'web-design' => [
        ['key' => 'full',  'label' => 'Pay in full', 'installments' => [
            ['pct' => 100, 'due_days' => 0, 'label' => ''],
        ]],
        ['key' => '50_50', 'label' => 'Pay 50 / 50 (deposit now, balance in 30 days)', 'installments' => [
            ['pct' => 50, 'due_days' => 0,  'label' => '50% deposit'],
            ['pct' => 50, 'due_days' => 30, 'label' => '50% balance'],
        ]],
    ],
    'seo' => [
        ['key' => 'monthly',    'label' => 'Pay monthly', 'installments' => [
            ['pct' => 100, 'due_days' => 0, 'label' => ''],
        ]],
        ['key' => 'fortnightly', 'label' => 'Pay fortnightly (split into two)', 'installments' => [
            ['pct' => 50, 'due_days' => 0,  'label' => 'first fortnight'],
            ['pct' => 50, 'due_days' => 14, 'label' => 'second fortnight'],
        ]],
    ],
    'hosting' => [
        ['key' => 'monthly', 'label' => 'Pay monthly', 'installments' => [
            ['pct' => 100, 'due_days' => 0, 'label' => '', 'months' => 1],
        ]],
        ['key' => 'yearly',  'label' => 'Pay yearly (12 months up front)', 'installments' => [
            ['pct' => 100, 'due_days' => 0, 'label' => '12 months', 'months' => 12],
        ]],
    ],
];
