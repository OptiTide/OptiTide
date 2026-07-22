<?php

use App\Models\LandingPage;

/**
 * Seed the starter keyword landing pages.
 *
 * Content lives in database/seeds/landing/*.json — one file per page — so the copy
 * can be edited by hand without touching PHP, and so a page can be restored after
 * an experiment by deleting it in the admin and re-running this.
 *
 * IDEMPOTENT: a page whose slug already exists is skipped, never overwritten. Once
 * a page is live it belongs to whoever edits it in the admin; a re-run must not
 * silently revert their work.
 *
 * Every factual claim in these files traces to config('company') or the real
 * service catalogue. They were drafted, then adversarially fact-checked for
 * invented claims (client counts, testimonials, service deliverables, server
 * locations, unsourced statistics) and rewritten — on a page built to attract
 * strangers, a false claim is worse than a missing one.
 */
return new class {
    /**
     * @param bool $refreshDrafts Re-import content over rows that are STILL DRAFT.
     *                            Off by default: the normal run must never overwrite
     *                            a page someone has edited. Use only to push a
     *                            correction into drafts nobody has published yet.
     */
    public function run(callable $out, bool $refreshDrafts = false): void
    {
        $dir = __DIR__ . '/landing';

        if (! is_dir($dir)) {
            $out('No landing page content found at database/seeds/landing — nothing to do.');

            return;
        }

        $files = glob($dir . '/*.json') ?: [];
        if ($files === []) {
            $out('No .json files in database/seeds/landing — nothing to do.');

            return;
        }

        $created = 0;
        $refreshed = 0;
        $skipped = 0;
        $bad = 0;

        foreach ($files as $file) {
            $page = json_decode((string) file_get_contents($file), true);

            if (! is_array($page) || empty($page['slug']) || empty($page['title'])) {
                $out('  SKIP ' . basename($file) . ' — not valid page JSON');
                $bad++;
                continue;
            }

            $slug = strtolower(trim($page['slug']));

            // service_slug is what makes the service page link to this one. A typo
            // here fails SILENTLY — the page seeds fine, resolves fine, and is simply
            // never linked from anywhere, which is the one thing that stops it
            // ranking. ("smm" instead of "social-media" did exactly that.) So warn
            // loudly and store null rather than a value nothing will ever match.
            // Read the valid slugs from the service pages themselves rather than a
            // second hardcoded list here — a copy would drift out of sync and
            // reintroduce the same silent failure one level up.
            // Computed BEFORE the exists-check so the refresh path validates too.
            $valid = array_keys(\App\Controllers\PublicSite\PageController::serviceData());
            $serviceSlug = strtolower(trim((string) ($page['service_slug'] ?? '')));
            if ($serviceSlug !== '' && ! in_array($serviceSlug, $valid, true)) {
                $out('  WARNING /' . $slug . ': unknown service_slug "' . $serviceSlug . '" — this page will not be linked from any service page.');
                $out('           valid: ' . implode(', ', $valid));
                $serviceSlug = '';
            }

            if (! LandingPage::slugAvailable($slug)) {
                // Either it already exists (fine — leave the live copy alone) or the
                // slug collides with a real route, which must never be published.
                $existing = $refreshDrafts ? LandingPage::findBySlug($slug) : null;

                // Refresh ONLY while still draft. A published page may have been
                // edited by hand, and silently reverting someone's work is worse
                // than leaving a correction unapplied — they can re-run this after
                // unpublishing if they really want the seed copy back.
                if ($existing && ($existing['status'] ?? null) === LandingPage::STATUS_DRAFT) {
                    LandingPage::updateById($existing['id'], [
                        'title'            => $page['title'],
                        'meta_title'       => $page['meta_title'] ?? null,
                        'meta_description' => $page['meta_description'] ?? null,
                        'keyword'          => $page['keyword'] ?? null,
                        'location'         => ($page['location'] ?? '') !== '' ? $page['location'] : null,
                        'service_slug'     => $serviceSlug !== '' ? $serviceSlug : null,
                        'intro'            => $page['intro'] ?? null,
                        'body'             => $page['body_html'] ?? '',
                        'faqs'             => ($page['faqs'] ?? []) !== [] ? json_encode($page['faqs'], JSON_UNESCAPED_UNICODE) : null,
                    ]);
                    $out('  refreshed draft: /' . $slug);
                    $refreshed++;
                    continue;
                }

                $out('  exists/reserved: /' . $slug . ($existing ? ' (published — left alone)' : ''));
                $skipped++;
                continue;
            }

            $faqs = is_array($page['faqs'] ?? null) ? $page['faqs'] : [];

            LandingPage::create([
                'slug'             => $slug,
                'title'            => $page['title'],
                'meta_title'       => $page['meta_title'] ?? null,
                'meta_description' => $page['meta_description'] ?? null,
                'keyword'          => $page['keyword'] ?? null,
                'location'         => ($page['location'] ?? '') !== '' ? $page['location'] : null,
                'service_slug'     => $serviceSlug !== '' ? $serviceSlug : null,
                'intro'            => $page['intro'] ?? null,
                'body'             => $page['body_html'] ?? '',
                'faqs'             => $faqs !== [] ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null,
                // Seeded as DRAFT on purpose. The owner should read a page before it
                // is public and in the sitemap — publishing eight pages nobody has
                // proof-read is how a wrong price or an awkward sentence ends up
                // being the first thing a stranger reads.
                'status'           => LandingPage::STATUS_DRAFT,
                'published_at'     => null,
            ]);

            $out('  created (draft): /' . $slug);
            $created++;
        }

        $out('');
        $out(sprintf('%d created as DRAFT, %d draft(s) refreshed, %d left alone, %d unreadable.', $created, $refreshed, $skipped, $bad));
        if ($created > 0) {
            $out('Review each one under Admin > Landing Pages, then switch it to Published.');
        }
    }
};
