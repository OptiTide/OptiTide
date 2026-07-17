<?php

use App\Core\Database;

/**
 * Point the 14 blog posts at their real licensed cover photos.
 *
 * The posts shipped with branded SVG placeholders; the owner wants real photos.
 * Each .jpg is an Unsplash photo used under the Unsplash License (commercial
 * use, no attribution) — provenance is recorded permanently in
 * public/assets/img/blog/CREDITS.md.
 *
 * cover_image is DATA, so the seeder can't fix production — this migration is
 * the only thing that reaches the live database on deploy.
 *
 * Only rows still holding the generated .svg (or nothing) are touched, so a
 * cover an admin has since set by hand in the blog form is never clobbered.
 * The .svg files stay on disk as the fallback.
 */
return new class {
    /** Slugs that now have a licensed photo at /assets/img/blog/<slug>.jpg. */
    private const SLUGS = [
        'how-much-does-a-website-cost-in-australia',
        'small-business-website-redesign-signs',
        'turn-website-visitors-into-customers',
        'what-is-seo-small-business-guide',
        'local-seo-google-maps-australia',
        'how-to-rank-higher-on-google',
        'google-business-profile-optimisation-guide',
        'keyword-research-for-small-business',
        'social-media-strategy-small-business',
        'how-often-to-post-on-social-media',
        'managed-vs-unmanaged-hosting',
        'why-website-speed-matters',
        'website-security-ssl-small-business',
        'digital-marketing-for-small-business',
    ];

    public function up(): void
    {
        $db = Database::instance();

        foreach (self::SLUGS as $slug) {
            $jpg = '/assets/img/blog/' . $slug . '.jpg';
            $svg = '/assets/img/blog/' . $slug . '.svg';

            $db->affecting(
                'UPDATE blogs SET cover_image = ?
                   WHERE slug = ?
                     AND (cover_image = ? OR cover_image IS NULL OR cover_image = ?)',
                [$jpg, $slug, $svg, '']
            );
        }
    }

    public function down(): void
    {
        $db = Database::instance();

        // Reversible: put back the SVG placeholder, but only where this
        // migration's own .jpg is still in place.
        foreach (self::SLUGS as $slug) {
            $db->affecting(
                'UPDATE blogs SET cover_image = ? WHERE slug = ? AND cover_image = ?',
                [
                    '/assets/img/blog/' . $slug . '.svg',
                    $slug,
                    '/assets/img/blog/' . $slug . '.jpg',
                ]
            );
        }
    }
};
