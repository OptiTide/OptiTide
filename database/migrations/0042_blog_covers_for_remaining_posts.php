<?php

use App\Core\Database;

/**
 * Give the remaining live posts a cover photo.
 *
 * Migration 0041 matched the 14 slugs the seeder creates, but PRODUCTION has 26
 * posts — a second set with different slugs that dev has never had. Those 12 had
 * no cover at all, so BlogController::imageUrl() fell back to favicon.png: a
 * 512px square served as the og:image, which is what a link to them looks like
 * when shared.
 *
 * Each is mapped to the licensed photo for the same topic (see
 * public/assets/img/blog/CREDITS.md). Two posts sharing a cover is fine — both
 * are about the same thing, which is itself the point flagged to the owner:
 * these are near-duplicates of the seeded articles.
 *
 * Empty-only, exactly like 0041 — a cover set by hand in admin is never touched.
 */
return new class {
    /** live slug => the licensed photo for that topic */
    private const COVERS = [
        'how-much-does-a-website-cost-in-australia-in-2026'                => 'how-much-does-a-website-cost-in-australia',
        '10-signs-your-small-business-website-needs-a-redesign'            => 'small-business-website-redesign-signs',
        'what-is-seo-and-why-does-your-business-need-it'                   => 'what-is-seo-small-business-guide',
        'local-seo-how-to-get-your-business-found-on-google-maps'          => 'local-seo-google-maps-australia',
        'how-to-rank-higher-on-google-a-beginner-s-guide'                  => 'how-to-rank-higher-on-google',
        'google-business-profile-the-free-tool-every-local-business-needs' => 'google-business-profile-optimisation-guide',
        'how-often-should-you-post-on-social-media'                        => 'how-often-to-post-on-social-media',
        '5-social-media-mistakes-australian-small-businesses-make'         => 'social-media-strategy-small-business',
        'managed-vs-unmanaged-hosting-which-do-you-need'                   => 'managed-vs-unmanaged-hosting',
        'why-website-speed-matters-and-how-to-fix-a-slow-site'             => 'why-website-speed-matters',
        'do-you-really-need-ssl-website-security-for-small-business'       => 'website-security-ssl-small-business',
        'how-to-get-more-customers-from-your-website'                      => 'turn-website-visitors-into-customers',
    ];

    public function up(): void
    {
        $db = Database::instance();

        foreach (self::COVERS as $slug => $photo) {
            $db->affecting(
                "UPDATE blogs SET cover_image = ?
                 WHERE slug = ? AND (cover_image IS NULL OR cover_image = '' OR cover_image LIKE '%.svg')",
                ['/assets/img/blog/' . $photo . '.jpg', $slug]
            );
        }
    }

    public function down(): void
    {
        $db = Database::instance();

        foreach (self::COVERS as $slug => $photo) {
            $db->affecting(
                'UPDATE blogs SET cover_image = NULL WHERE slug = ? AND cover_image = ?',
                [$slug, '/assets/img/blog/' . $photo . '.jpg']
            );
        }
    }
};
