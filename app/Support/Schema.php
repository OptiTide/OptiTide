<?php

namespace App\Support;

/**
 * JSON-LD builders for the public pages.
 *
 * Every value comes from config('company') — the admin-editable settings — and any
 * field that is blank is DROPPED rather than guessed. Structured data is a set of
 * machine-readable claims about a real business: inventing a phone number or a
 * street address to fill a schema slot is publishing a false claim about the
 * company, and Google penalises structured data that contradicts the page.
 * Right now phone, street address and social profiles are unset, so they are
 * simply absent — the schema grows itself the moment they are filled in.
 */
final class Schema
{
    /** The publisher/provider block every other schema references. */
    public static function organization(): array
    {
        $company = config('company');
        $appUrl = rtrim(config('app.url'), '/');

        $org = [
            '@type'  => 'ProfessionalService',
            '@id'    => $appUrl . '/#organization',
            'name'   => $company['brand_name'],
            'legalName' => $company['legal_name'] ?? null,
            'url'    => $appUrl,
            'logo'   => $appUrl . '/assets/img/logo.png',
            'image'  => $appUrl . '/assets/img/og-default.png',
            'email'  => $company['email'] ?: null,
            'telephone' => $company['phone'] ?: null,
            // The ABN is a real, verifiable identifier for an Australian business.
            'identifier' => ! empty($company['abn'])
                ? ['@type' => 'PropertyValue', 'name' => 'ABN', 'value' => $company['abn']]
                : null,
            'areaServed' => ['@type' => 'Country', 'name' => 'Australia'],
            'address' => self::address(),
            'openingHours' => $company['hours'] ?: null,
            'sameAs' => self::socialProfiles(),
        ];

        return self::prune($org);
    }

    /** PostalAddress from whatever is actually configured, or null. */
    private static function address(): ?array
    {
        $a = config('company.address', []);

        // No streetAddress on purpose. This renders into public JSON-LD, which is
        // machine-readable and scraped far more aggressively than body text — and
        // the registered address is a home address with no premises to visit.
        // Suburb + state is enough for local relevance. Invoices, which legally
        // DO need the street, build their address separately via
        // Company::addressLine() and are unaffected by this.
        $address = self::prune([
            '@type'           => 'PostalAddress',
            'addressLocality' => $a['locality'] ?? null,
            'addressRegion'   => $a['region'] ?? null,
            'postalCode'      => $a['postcode'] ?? null,
            'addressCountry'  => $a['country'] ?? 'Australia',
        ]);

        // A country on its own is not an address worth publishing.
        return count($address) > 2 ? $address : null;
    }

    /** @return string[]|null only the social URLs that are set */
    private static function socialProfiles(): ?array
    {
        $urls = array_values(array_filter(array_map(
            fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null,
            (array) config('company.social', [])
        )));

        return $urls !== [] ? $urls : null;
    }

    /** A single service line. */
    public static function service(string $name, string $description, string $url): array
    {
        return self::prune([
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $name,
            'description' => $description,
            'url'         => $url,
            'serviceType' => $name,
            'provider'    => self::organization(),
            'areaServed'  => ['@type' => 'Country', 'name' => 'Australia'],
        ]);
    }

    /** A plain content page (about, how-we-work, legal). */
    public static function webPage(string $name, string $description, string $url, string $type = 'WebPage'): array
    {
        $appUrl = rtrim(config('app.url'), '/');

        return self::prune([
            '@context'    => 'https://schema.org',
            '@type'       => $type,
            'name'        => $name,
            'description' => $description,
            'url'         => $url,
            'inLanguage'  => 'en-AU',
            'isPartOf'    => ['@type' => 'WebSite', 'name' => config('company.brand_name'), 'url' => $appUrl],
            'publisher'   => self::organization(),
        ]);
    }

    /** The services index — a list pointing at each service page. */
    public static function serviceList(array $services, string $url): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $items = [];
        $position = 1;

        foreach ($services as $slug => $service) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $service['title'] ?? $slug,
                'url'      => $appUrl . '/services/' . $slug,
            ];
        }

        return self::prune([
            '@context'        => 'https://schema.org',
            '@type'           => 'CollectionPage',
            'name'            => 'Services',
            'url'             => $url,
            'inLanguage'      => 'en-AU',
            'publisher'       => self::organization(),
            'mainEntity'      => ['@type' => 'ItemList', 'itemListElement' => $items],
        ]);
    }

    /** Drop nulls, empty strings and empty arrays, recursively — and repair encoding. */
    private static function prune(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::prune($value);
                $data[$key] = $value;
            } elseif (is_string($value)) {
                $data[$key] = $value = self::utf8($value);
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Force a string to valid UTF-8.
     *
     * These values come from admin-editable settings, and json_encode fails on the
     * WHOLE structure if any byte is invalid — so one en-dash pasted out of Word
     * (Windows-1252 0x96 rather than UTF-8 e2 80 93) would silently delete the
     * JSON-LD from every page on the site. Caught exactly that in a dev database.
     * Converting is right rather than escaping the failure: the text is legitimate,
     * only its encoding is wrong.
     */
    private static function utf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Windows-1252 is what Word, Outlook and Excel paste; it is a superset of
        // Latin-1 and covers the smart quotes and dashes that cause this.
        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }
}
