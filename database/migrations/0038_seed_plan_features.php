<?php

use App\Core\Database;

/**
 * Backfill the public "What's included" bullets for the seeded plans.
 *
 * These are DATA, not code, so a fresh dev seed and the live database are
 * separate — hence a migration rather than a one-off script: it runs on deploy
 * and reaches production.
 *
 * Only fills a plan whose features are still empty, and matches on the exact
 * seeded name. So it never overwrites bullets written in /admin/services, and a
 * renamed or hand-added plan is left alone.
 */
return new class {
    /** Plan name => bullets, one per line. */
    private const FEATURES = [
        'Starter Website' => [
            'Up to 5 pages',
            'Custom design — not a template',
            'Mobile-first & fast loading',
            'Contact form & Google Maps',
            'Search-friendly build',
            '2 rounds of revisions',
        ],
        'Business Website' => [
            'Everything in Starter',
            'Up to 12 pages',
            'Blog or news section',
            'E-Commerce or bookings ready',
            'Google Analytics & Search Console',
            '3 rounds of revisions',
        ],
        'Custom Website' => [
            'Scoped to your project',
            'Custom functionality & integrations',
            'Unlimited pages',
        ],
        'SEO Essentials' => [
            'Technical SEO audit & fixes',
            'On-page optimisation — 10 keywords',
            'Google Business Profile & local SEO',
            'Monthly ranking & traffic report',
        ],
        'SEO Growth' => [
            'Everything in Essentials',
            'Up to 25 keywords',
            'Monthly content & blog posts',
            'Link building & citations',
        ],
        'Custom SEO' => [
            'Everything in Growth',
            'Unlimited keywords',
            'Dedicated strategist',
        ],
        'Social Media Management' => [
            '12 posts per month',
            '2 platforms of your choice',
            'Content calendar & scheduling',
            'Community management',
            'Monthly performance report',
        ],
        'Unmanaged Hosting' => [
            'Australian server',
            'Free SSL certificate',
            'Daily backups',
            'Uptime monitoring',
            'You handle your own updates',
        ],
        'Managed Hosting' => [
            'Everything in Unmanaged',
            'We handle platform updates',
            'Security patching & hardening',
            'Priority support in your timezone',
        ],
    ];

    public function up(): void
    {
        $db = Database::instance();

        foreach (self::FEATURES as $name => $lines) {
            // Empty-or-null only: never clobber bullets edited in the admin.
            $db->affecting(
                "UPDATE services SET features = ? WHERE name = ? AND (features IS NULL OR features = '')",
                [implode("\n", $lines), $name]
            );
        }
    }

    public function down(): void
    {
        $db = Database::instance();

        // Only clear what this migration would have written, so bullets edited
        // since are kept.
        foreach (self::FEATURES as $name => $lines) {
            $db->affecting(
                'UPDATE services SET features = NULL WHERE name = ? AND features = ?',
                [$name, implode("\n", $lines)]
            );
        }
    }
};
