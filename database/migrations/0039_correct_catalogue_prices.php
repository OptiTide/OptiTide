<?php

use App\Core\Database;

/**
 * Correct the live catalogue prices.
 *
 * Production drifted from the real price list: Starter Website was A$1,499
 * (should be $750), Business Website A$2,999 (should be $1,500) and Social Media
 * Management A$790 (should be $250). SEO and Hosting were already right.
 *
 * Prices are DATA, so a code change can't fix them — this migration is the only
 * thing that reaches the production database on deploy.
 *
 * Prices confirmed by the owner: web design $750 / $1,500 (+ Custom = quote),
 * SEO $750 / $1,500 / $2,500, SMM $250, hosting $25 unmanaged / $50 managed.
 * Integer cents, GST-inclusive, per the house money rule.
 */
return new class {
    /** Plan name => correct price in cents. */
    private const PRICES = [
        'Starter Website'         => 75000,
        'Business Website'        => 150000,
        'Custom Website'          => 0,       // quote-based
        'SEO Essentials'          => 75000,
        'SEO Growth'              => 150000,
        'Custom SEO'              => 250000,
        'Social Media Management' => 25000,
        'Unmanaged Hosting'       => 2500,
        'Managed Hosting'         => 5000,
    ];

    public function up(): void
    {
        $db = Database::instance();

        foreach (self::PRICES as $name => $cents) {
            // Exact-name match, and only where the price actually differs — so
            // the migration is a no-op on an already-correct database and can't
            // touch a plan that happens to share a prefix.
            $db->affecting(
                'UPDATE services SET price_cents = ? WHERE name = ? AND price_cents <> ?',
                [$cents, $name, $cents]
            );
        }
    }

    public function down(): void
    {
        // No down: the previous values were wrong, and restoring them would put
        // incorrect prices back on a public page.
    }
};
