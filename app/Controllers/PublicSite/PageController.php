<?php

namespace App\Controllers\PublicSite;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Captcha;
use App\Support\Catalog;

/**
 * Standalone marketing pages (Services overview, per-service detail, About,
 * Contact). Content is defined here so it's easy to edit; pages render through
 * the indexable marketing layout with the shared nav + footer.
 */
class PageController extends Controller
{
    /** Detailed, editable content for each service page (keyed by slug). */
    public static function serviceData(): array
    {
        return [
            'web-design' => [
                'category' => 'web-design',   // matches the service_categories slug
                'icon' => 'bi-window-desktop',
                'nav' => 'Web Design',
                'title' => 'Web Design & Development',
                'h1' => 'Websites That Win You Customers',
                'intro' => 'Your website is often the first impression a customer has of your business. We design and build fast, modern, mobile-first websites that look professional and turn visitors into enquiries and sales — built around your brand and your goals, never a generic template.',
                'includes' => [
                    ['bi-brush', 'Custom design', 'A unique design built around your brand — not a template every other business is using.'],
                    ['bi-phone', 'Mobile-first & fast', 'Looks and works perfectly on every device, and loads fast so visitors don\'t bounce.'],
                    ['bi-search', 'SEO-ready build', 'Clean, search-friendly code so you can rank on Google from day one.'],
                    ['bi-pencil-square', 'Easy to edit', 'A simple content manager so you can update text and images yourself.'],
                    ['bi-cart', 'E-commerce & bookings', 'Sell products or take bookings online with secure payments.'],
                    ['bi-shield-check', 'Ongoing care', 'Managed hosting, security, backups and updates so your site stays healthy.'],
                ],
                'benefits' => ['Convert more visitors into leads and sales', 'Rank better on Google from launch', 'Look professional and trustworthy', 'A site you actually own and control'],
            ],
            'seo' => [
                'category' => 'seo',
                'icon' => 'bi-search',
                'nav' => 'SEO',
                'title' => 'Search Engine Optimisation',
                'h1' => 'Get Found by Customers Searching for You',
                'intro' => 'Most buying journeys start on Google. We combine technical SEO, on-page optimisation, local search and content to grow your qualified organic traffic over time — so the right customers find you instead of your competitors.',
                'includes' => [
                    ['bi-clipboard-data', 'Technical SEO audit', 'We find and fix the technical issues holding your rankings back.'],
                    ['bi-file-earmark-text', 'On-page optimisation', 'Titles, content and structure optimised for the terms your customers search.'],
                    ['bi-geo-alt', 'Local SEO', 'Google Business Profile and local citations so you show up in the map pack.'],
                    ['bi-key', 'Keyword & content strategy', 'A plan targeting the searches most likely to bring you business.'],
                    ['bi-link-45deg', 'Links & citations', 'Building your authority with quality, relevant backlinks and listings.'],
                    ['bi-graph-up-arrow', 'Transparent reporting', 'Clear monthly reports on rankings, traffic and what we did.'],
                ],
                'benefits' => ['More qualified organic traffic', 'Higher rankings for terms that convert', 'Show up in Google Maps locally', 'Compounding results that build over time'],
            ],
            'social-media' => [
                'category' => 'smm',   // the DB line is slugged "smm"
                'icon' => 'bi-megaphone',
                'nav' => 'Social Media',
                'title' => 'Social Media Marketing',
                'h1' => 'Show Up Where Your Customers Are',
                'intro' => 'A consistent, on-brand social presence builds awareness, engagement and trust. We plan, create and manage content across your channels — and run paid campaigns when you want to reach more people, faster.',
                'includes' => [
                    ['bi-calendar-week', 'Content calendar', 'A planned monthly calendar so you\'re always posting with purpose.'],
                    ['bi-palette', 'On-brand content', 'Scroll-stopping graphics and copy that sound like you.'],
                    ['bi-send', 'Scheduling & publishing', 'We handle posting across the platforms that matter for your business.'],
                    ['bi-chat-dots', 'Community management', 'Replying to comments and messages so no lead goes cold.'],
                    ['bi-badge-ad', 'Paid social advertising', 'Targeted ad campaigns to reach and convert new audiences.'],
                    ['bi-bar-chart', 'Performance reporting', 'Regular reporting on reach, engagement and results.'],
                ],
                'benefits' => ['Stay top-of-mind with your audience', 'Build brand trust and recognition', 'Reach new customers with paid campaigns', 'Save hours every week'],
            ],
            'hosting' => [
                'category' => 'hosting',
                'icon' => 'bi-hdd-network',
                'nav' => 'Managed Hosting',
                'title' => 'Managed Web Hosting',
                'h1' => 'Fast, Secure, Fully-Managed Hosting',
                'intro' => 'Reliable Australian hosting so your website is always fast, safe and online. We handle the servers, security, backups and monitoring — you focus on your business, and we\'re here the moment you need us.',
                'includes' => [
                    ['bi-server', 'Australian servers', 'Fast, local hosting for great performance for your Australian visitors.'],
                    ['bi-lock', 'Free SSL & security', 'SSL certificates and security hardening included as standard.'],
                    ['bi-clock-history', 'Daily backups', 'Automated daily backups so your site can always be restored.'],
                    ['bi-activity', 'Uptime monitoring', 'We watch your site around the clock and act on issues fast.'],
                    ['bi-arrow-repeat', 'Managed updates', 'We keep the platform patched, secure and up to date.'],
                    ['bi-headset', 'Priority support', 'A real person to help — not a ticket queue in another timezone.'],
                ],
                'benefits' => ['Peace of mind — it just works', 'Fast load times that help SEO', 'Protected against data loss', 'Local support in your timezone'],
            ],
        ];
    }

    public function services(Request $request): Response
    {
        // Real "from" prices straight out of the admin-managed catalogue.
        $services = self::serviceData();
        foreach ($services as $slug => $s) {
            $services[$slug]['from'] = Catalog::fromPriceCents($s['category']);
        }

        $brand = config('company.brand_name');

        return $this->view('public.pages.services', [
            'seoTitle' => 'Our Services — Web Design, SEO, Social Media & Hosting | ' . $brand,
            'seoDescription' => $brand . '\'s digital services for Australian business: web design & development, SEO, social media marketing and managed hosting — all under one roof.',
            'canonical' => rtrim(config('app.url'), '/') . '/services',
            'services' => $services,
        ]);
    }

    public function service(Request $request, string $slug): Response
    {
        $all = self::serviceData();
        if (! isset($all[$slug])) {
            return $this->view('errors.generic', ['title' => 'Not found', 'status' => 404, 'message' => 'That service page could not be found.'], 404);
        }
        $service = $all[$slug];

        // Real plans + prices for this line, straight from the admin catalogue.
        $isAuthed = Auth::check();

        return $this->view('public.pages.service', [
            'seoTitle' => $service['title'] . ' for Australian Business | ' . config('company.brand_name'),
            'seoDescription' => $service['intro'],
            'canonical' => rtrim(config('app.url'), '/') . '/services/' . $slug,
            'slug' => $slug,
            'service' => $service,
            'plans' => Catalog::plansForSlug($service['category']),
            'canOrder' => $isAuthed && Auth::isClient(),
            'startUrl' => $isAuthed ? route('portal.order.index') : route('register'),
            'others' => array_diff_key($all, [$slug => true]),
        ]);
    }

    public function about(Request $request): Response
    {
        $brand = config('company.brand_name');

        return $this->view('public.pages.about', [
            'seoTitle' => 'About ' . $brand . ' — Australian Digital Agency',
            'seoDescription' => $brand . ' is an Australian-owned digital agency helping small businesses grow online with web design, SEO, social media and hosting — honest advice, fixed pricing, no lock-in contracts.',
            'canonical' => rtrim(config('app.url'), '/') . '/about',
        ]);
    }

    /**
     * How We Work — the timezone story. The office clock and the visitor
     * comparison are computed live (server for ours, browser for theirs), so the
     * page can't go stale and can't claim an advantage for someone who shares
     * our clock.
     */
    public function howWeWork(Request $request): Response
    {
        $brand = config('company.brand_name');

        return $this->view('public.pages.how-we-work', [
            'seoTitle'       => 'How We Work — ' . $brand . ' | ' . \App\Support\Company::timezoneAbbr() . ' hours, overnight turnaround',
            'seoDescription' => 'We work ' . \App\Support\Company::timezoneAbbr() . ' hours from Western Australia and often late into the evening — so you send feedback, we work while you sleep, and you review it the next morning.',
            'canonical'      => rtrim(config('app.url'), '/') . '/how-we-work',
        ]);
    }

    public function contact(Request $request): Response
    {
        $brand = config('company.brand_name');

        return $this->view('public.pages.contact', [
            'seoTitle' => 'Contact ' . $brand . ' — Get a Free Quote',
            'seoDescription' => 'Get in touch with ' . $brand . ' for a free, no-obligation quote on web design, SEO, social media or hosting for your Australian business.',
            'canonical' => rtrim(config('app.url'), '/') . '/contact',
            'captcha' => Captcha::question(),
        ]);
    }
}
