<?php

namespace App\Services\Seo;

use App\Models\Backlink;

/**
 * Backlink / citation building toolkit. We can't (and shouldn't) auto-post links
 * to third-party sites — automated link spam breaches search-engine guidelines
 * and can get a site penalised. Instead this curates a starter list of
 * high-quality, legitimate Australian citation sources for the admin to submit
 * to, and tracks the whole link profile (prospect -> submitted -> live).
 */
final class BacklinkService
{
    /**
     * High-value AU citation sources + agency directories. Consistent NAP
     * (Name/Address/Phone) across these builds local-SEO trust; the agency
     * directories earn topical, higher-authority links.
     *
     * Each row is positional: [site_name, site_url, submit_url, type, domain_authority, notes].
     *
     * @return array<int,array{0:string,1:string,2:string,3:string,4:int,5:string}>
     */
    public static function starterDirectories(): array
    {
        return [
            ['Google Business Profile', 'https://www.google.com/business/', 'https://business.google.com/create', 'citation', 100, 'The single most important listing — drives Google Maps + local pack. Verify by post/phone.'],
            ['Bing Places for Business', 'https://www.bingplaces.com/', 'https://www.bingplaces.com/', 'citation', 93, 'Powers Bing + can import straight from Google Business Profile.'],
            ['Apple Business Connect', 'https://businessconnect.apple.com/', 'https://businessconnect.apple.com/', 'citation', 100, 'Shows your business in Apple Maps / Siri.'],
            ['True Local', 'https://www.truelocal.com.au/', 'https://www.truelocal.com.au/', 'directory', 72, 'Major AU business directory.'],
            ['Yellow Pages Australia', 'https://www.yellowpages.com.au/', 'https://www.yellowpages.com.au/', 'directory', 84, 'Long-standing AU directory; free + paid listings.'],
            ['White Pages Australia', 'https://www.whitepages.com.au/', 'https://www.whitepages.com.au/', 'directory', 80, 'Free business listing.'],
            ['Hotfrog Australia', 'https://www.hotfrog.com.au/', 'https://www.hotfrog.com.au/', 'directory', 70, 'Free AU business directory.'],
            ['StartLocal', 'https://www.startlocal.com.au/', 'https://www.startlocal.com.au/', 'directory', 55, 'AU local directory, free listing.'],
            ['Yelp Australia', 'https://www.yelp.com.au/', 'https://biz.yelp.com.au/', 'directory', 94, 'Reviews + citation; claim your business page.'],
            ['AussieWeb', 'https://www.aussieweb.com.au/', 'https://www.aussieweb.com.au/', 'directory', 50, 'Australian search directory.'],
            ['dLook', 'https://www.dlook.com.au/', 'https://www.dlook.com.au/', 'directory', 48, 'Free AU business directory.'],
            ['Localsearch', 'https://www.localsearch.com.au/', 'https://www.localsearch.com.au/', 'directory', 66, 'AU local business directory + reviews.'],
            ['Word of Mouth', 'https://www.wordofmouth.com.au/', 'https://www.wordofmouth.com.au/', 'directory', 60, 'AU reviews-led directory.'],
            ['Brownbook', 'https://www.brownbook.net/', 'https://www.brownbook.net/', 'directory', 58, 'Global directory, accepts AU listings.'],
            ['Cylex Australia', 'https://www.cylex.com.au/', 'https://www.cylex.com.au/', 'directory', 52, 'Free AU business directory.'],
            ['Clutch', 'https://clutch.co/', 'https://clutch.co/get-listed', 'directory', 91, 'Agency directory — high-authority topical link for a digital agency.'],
            ['DesignRush', 'https://www.designrush.com/', 'https://www.designrush.com/agency/submit', 'directory', 76, 'Agency listing directory (web design / SEO / marketing).'],
            ['GoodFirms', 'https://www.goodfirms.co/', 'https://www.goodfirms.co/get-listed', 'directory', 78, 'Agency + software directory.'],
            ['LinkedIn Company Page', 'https://www.linkedin.com/', 'https://www.linkedin.com/company/setup/new/', 'social', 98, 'Company profile — social citation + credibility.'],
            ['Facebook Business Page', 'https://www.facebook.com/business', 'https://www.facebook.com/pages/create', 'social', 96, 'Social citation; link your website + NAP.'],
            ['Instagram Business', 'https://business.instagram.com/', 'https://business.instagram.com/', 'social', 94, 'Bio link to your site.'],
        ];
    }

    /**
     * Insert any starter directories not already present (matched by site_name).
     * Idempotent — safe to run repeatedly. Returns the number added.
     */
    public function seedStarter(): int
    {
        $existing = array_column(Backlink::query()->get(), 'site_name');
        $added = 0;
        foreach (self::starterDirectories() as [$siteName, $siteUrl, $submitUrl, $type, $da, $notes]) {
            if (in_array($siteName, $existing, true)) {
                continue;
            }
            Backlink::create([
                'site_name'        => $siteName,
                'site_url'         => $siteUrl,
                'submit_url'       => $submitUrl,
                'type'             => $type,
                'status'           => Backlink::STATUS_PROSPECT,
                'domain_authority' => $da,
                'notes'            => $notes,
            ]);
            $added++;
        }

        return $added;
    }

    /** Counts for the dashboard summary. */
    public function summary(): array
    {
        $all = Backlink::query()->get();
        $by = [Backlink::STATUS_PROSPECT => 0, Backlink::STATUS_SUBMITTED => 0, Backlink::STATUS_LIVE => 0, Backlink::STATUS_REJECTED => 0];
        foreach ($all as $b) {
            $by[$b['status']] = ($by[$b['status']] ?? 0) + 1;
        }

        return ['total' => count($all)] + $by;
    }
}
