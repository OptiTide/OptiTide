<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $url = rtrim(config('app.url'), '/');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /$',
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
            ['loc' => $url . '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $url . '/login', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($u['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . $today . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
