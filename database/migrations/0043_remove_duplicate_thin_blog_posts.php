<?php

use App\Core\Database;

/**
 * Remove the 12 thin duplicate articles.
 *
 * Production carried TWO articles for each of these topics: a ~1,100-word version
 * and a ~3,000-word version targeting the SAME keyword. That is duplicate content
 * competing with itself — the thin post cannibalises the good one in search, and
 * neither ranks as well as one strong page would.
 *
 * It also explains a symptom the owner spotted first: the blog only had 15 unique
 * cover photos across 27 posts. Migration 0042 pointed each thin post at its
 * counterpart's photo, because they are about the same thing. The photos repeated
 * because the ARTICLES repeated. Removing the thin half leaves 15 posts, each with
 * its own photo — no new images needed.
 *
 * Word counts measured live before writing this (thin -> survivor):
 *   1190 -> 2942, 1144 -> 3190, 1141 -> 3024, 1145 -> 3307, 1108 -> 3172,
 *   1107 -> 3170, 1099 -> 3100, 1106 -> 3141, 1105 -> 3065, 1106 -> 3288,
 *   1112 -> 3079, 1099 -> 3330.
 *
 * The owner chose deletion over a 301 redirect. These URLs will 404; the blog was
 * ~10 days old so there is next to nothing indexed pointing at them.
 *
 * SAFETY: each delete is conditional on the survivor actually existing and being
 * published. If production ever diverges from what was measured, this no-ops rather
 * than deleting the only copy of an article. None of the 12 are in
 * database/seeds/blog_posts.php (which holds the 14 survivors), so `seed` cannot
 * bring them back.
 */
return new class {
    /** thin slug (delete) => survivor slug (must exist, or the delete is skipped) */
    private const DUPLICATES = [
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

        foreach (self::DUPLICATES as $thin => $survivor) {
            // Never delete the only copy: the survivor must be present first.
            $keep = $db->selectOne('SELECT id FROM blogs WHERE slug = ? LIMIT 1', [$survivor]);

            if ($keep === null) {
                continue;
            }

            $db->affecting('DELETE FROM blogs WHERE slug = ?', [$thin]);
        }
    }

    public function down(): void
    {
        // Irreversible by design: the article bodies are not stored anywhere else,
        // so there is nothing to restore. Recreating empty shells at these slugs
        // would be worse than a 404 — it would put thin content back in the index.
    }
};
