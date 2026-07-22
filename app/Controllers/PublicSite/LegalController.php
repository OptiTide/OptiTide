<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class LegalController extends Controller
{
    /**
     * These pages carried a <title> and nothing else — no description, canonical,
     * OG or Twitter tags — while being served noindex AND listed in sitemap.xml.
     * They index now, so they need real metadata. Descriptions describe what the
     * page actually contains; nothing here claims anything the policies don't say.
     */
    public function terms(Request $request): Response
    {
        return $this->view('public.legal.terms', $this->seo(
            'Terms of Service',
            'The terms covering ' . config('company.brand_name') . ' web design, SEO, social media and hosting engagements — scope, payment, ownership and cancellation.',
            '/terms'
        ));
    }

    public function privacy(Request $request): Response
    {
        return $this->view('public.legal.privacy', $this->seo(
            'Privacy Policy',
            'How ' . config('company.brand_name') . ' collects, uses and stores your information, who we share it with, and how to request access or deletion.',
            '/privacy'
        ));
    }

    public function refund(Request $request): Response
    {
        return $this->view('public.legal.refund', $this->seo(
            'Refund & Cancellation Policy',
            'When refunds apply, how to cancel a service or retainer, and what happens to work already completed — plus your rights under Australian Consumer Law.',
            '/refund'
        ));
    }

    /** Shared SEO payload so a new legal page can't ship without one. */
    private function seo(string $title, string $description, string $path): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $brand = config('company.brand_name');

        return [
            'title'          => $title,
            'seoTitle'       => $title . ' | ' . $brand,
            'seoDescription' => $description,
            'canonical'      => $appUrl . $path,
            'jsonLd'         => [
                '@context' => 'https://schema.org',
                '@type'    => 'WebPage',
                'name'     => $title,
                'url'      => $appUrl . $path,
                'description' => $description,
                'inLanguage'  => 'en-AU',
                'isPartOf'    => ['@type' => 'WebSite', 'name' => $brand, 'url' => $appUrl],
                'publisher'   => ['@type' => 'Organization', 'name' => $brand, 'url' => $appUrl],
            ],
        ];
    }
}
