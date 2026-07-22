<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\LandingPage;
use App\Support\Catalog;
use App\Support\HtmlSanitizer;
use App\Support\Schema;

/**
 * Keyword landing pages at the site root (/web-design-perth).
 *
 * Its route is registered LAST and 404s on any slug that isn't a published page,
 * so it can never swallow a real route. The slug is also validated against
 * LandingPage::RESERVED at write time, belt and braces.
 */
class LandingController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $page = LandingPage::live($slug);

        if (! $page) {
            $this->abort(404, 'Page not found.');
        }

        // Best-effort counter; never block the render on it.
        try {
            LandingPage::updateById($page['id'], ['views' => (int) ($page['views'] ?? 0) + 1]);
        } catch (\Throwable $e) {
            // ignore
        }

        $appUrl = rtrim(config('app.url'), '/');
        $canonical = $appUrl . '/' . $page['slug'];
        $brand = config('company.brand_name');
        $description = (string) ($page['meta_description'] ?: $page['intro']);
        $faqs = LandingPage::faqs($page);

        // Real plans from the admin catalogue when the page sells a service line —
        // so a landing page can never advertise a price the checkout won't honour.
        $plans = ! empty($page['service_slug']) ? Catalog::plansForSlug($page['service_slug']) : [];

        $graph = [Schema::webPage($page['title'], $description, $canonical)];

        if ($faqs !== []) {
            // FAQPage is the cheapest route onto page one for a long-tail query: it
            // can win the "People also ask" slot without outranking anybody.
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type' => 'Question',
                    'name'  => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                ], $faqs),
            ];
        }

        return $this->view('public.landing.show', [
            'title'          => $page['title'],
            'seoTitle'       => ($page['meta_title'] ?: $page['title']) . ' | ' . $brand,
            'seoDescription' => $description,
            'canonical'      => $canonical,
            'jsonLd'         => ['@context' => 'https://schema.org', '@graph' => $graph],
            'page'           => $page,
            // Admin-authored HTML, but staff are not a security boundary — a
            // compromised staff login would otherwise be stored XSS on a public page.
            'body'           => HtmlSanitizer::clean((string) $page['body']),
            'faqs'           => $faqs,
            'plans'          => $plans,
        ]);
    }
}
