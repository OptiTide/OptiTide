<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Blog;

class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $url = rtrim(config('app.url'), '/');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /$',
            'Allow: /blog',
            'Allow: /login',
            'Allow: /register',
            'Disallow: /admin',
            'Disallow: /portal',
            'Disallow: /pay/',
            '',
            'Sitemap: ' . $url . '/sitemap.xml',
            '',
        ]);

        return Response::make($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(Request $request): Response
    {
        $url = rtrim(config('app.url'), '/');
        $today = today();

        $urls = [
            ['loc' => $url . '/', 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => $url . '/services', 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => $url . '/about', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => $url . '/careers', 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => $url . '/contact', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => $url . '/blog', 'priority' => '0.8', 'changefreq' => 'daily', 'lastmod' => $today],
            ['loc' => $url . '/terms', 'priority' => '0.2', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => $url . '/privacy', 'priority' => '0.2', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => $url . '/refund', 'priority' => '0.2', 'changefreq' => 'yearly', 'lastmod' => $today],
            ['loc' => $url . '/login', 'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $today],
        ];

        // The money pages — driven off the same map the nav and routes use, so a
        // new service page can never be missing from the sitemap.
        foreach (array_keys(\App\Controllers\PublicSite\PageController::serviceData()) as $slug) {
            $urls[] = ['loc' => $url . '/services/' . $slug, 'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $today];
        }

        // Only OPEN roles — a draft isn't public and a filled role must drop out
        // of the index rather than linger as a dead JobPosting.
        foreach (\App\Models\JobOpening::open() as $role) {
            $urls[] = [
                'loc'        => $url . '/careers/' . $role['slug'],
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d', strtotime((string) ($role['updated_at'] ?: ($role['posted_at'] ?: $today)))),
            ];
        }

        foreach (Blog::published() as $post) {
            $urls[] = [
                'loc'        => $url . '/blog/' . $post['slug'],
                'priority'   => '0.7',
                'changefreq' => 'monthly',
                'lastmod'    => date('Y-m-d', strtotime($post['updated_at'] ?: ($post['published_at'] ?: $today))),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($u['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** RSS 2.0 feed of the most recent published posts. */
    public function rss(Request $request): Response
    {
        $url = rtrim(config('app.url'), '/');
        $posts = Blog::published(30);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0"><channel>' . "\n";
        $xml .= '  <title>' . e(config('company.legal_name', 'OptiTide') . ' Blog') . "</title>\n";
        $xml .= '  <link>' . e($url . '/blog') . "</link>\n";
        $xml .= '  <description>Web design, SEO, social media and marketing tips for Australian business.</description>' . "\n";
        $xml .= '  <language>en-AU</language>' . "\n";

        foreach ($posts as $p) {
            $link = $url . '/blog/' . $p['slug'];
            $xml .= "  <item>\n";
            $xml .= '    <title>' . e($p['title']) . "</title>\n";
            $xml .= '    <link>' . e($link) . "</link>\n";
            $xml .= '    <guid isPermaLink="true">' . e($link) . "</guid>\n";
            $xml .= '    <pubDate>' . e(date(DATE_RSS, strtotime($p['published_at'] ?: 'now'))) . "</pubDate>\n";
            $xml .= '    <description>' . e((string) ($p['excerpt'] ?: '')) . "</description>\n";
            $xml .= "  </item>\n";
        }

        $xml .= '</channel></rss>' . "\n";

        return Response::make($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
