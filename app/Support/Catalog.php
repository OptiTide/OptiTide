<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceCategory;

/**
 * The real, admin-managed service catalogue (service_categories + services).
 * Single source of truth for public pricing — the homepage and every service
 * page read from here, so prices always match what's in the portal.
 */
final class Catalog
{
    /** Quote-based plans (price 0) sort last; everything else cheapest first. */
    private static function sortPlans(array &$plans): void
    {
        usort($plans, function ($a, $b) {
            $pa = (int) $a['price_cents'];
            $pb = (int) $b['price_cents'];
            if (($pa === 0) !== ($pb === 0)) {
                return $pa === 0 ? 1 : -1;
            }

            return $pa <=> $pb;
        });
    }

    /**
     * Active plans grouped by service line.
     *
     * @return array<int,array{line:array,plans:array}>
     */
    public static function grouped(): array
    {
        $services = Service::active();
        $groups = [];

        foreach (ServiceCategory::ordered() as $line) {
            $plans = array_values(array_filter(
                $services,
                fn ($s) => (string) $s['category_id'] === (string) $line['id']
            ));
            self::sortPlans($plans);
            if ($plans !== []) {
                $groups[] = ['line' => $line, 'plans' => $plans];
            }
        }

        return $groups;
    }

    /** The active plans for one service line, by its category slug. */
    public static function plansForSlug(string $categorySlug): array
    {
        $line = ServiceCategory::firstWhere('slug', $categorySlug);
        if (! $line) {
            return [];
        }

        $plans = array_values(array_filter(
            Service::active(),
            fn ($s) => (string) $s['category_id'] === (string) $line['id']
        ));
        self::sortPlans($plans);

        return $plans;
    }

    /** Cheapest real (non-quote) price in a line, in cents — null if none. */
    public static function fromPriceCents(string $categorySlug): ?int
    {
        $prices = array_filter(array_map(
            fn ($p) => (int) $p['price_cents'],
            self::plansForSlug($categorySlug)
        ));

        return $prices ? min($prices) : null;
    }

    /** "/mo", "/yr" … for a recurring plan; empty string for one-off. */
    public static function suffix(array $plan): string
    {
        if (($plan['billing_type'] ?? '') !== 'recurring') {
            return '';
        }

        return '/' . substr((string) ($plan['interval'] ?: 'month'), 0, 2);
    }
}
