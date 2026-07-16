<?php

namespace App\Support;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Billing\DiscountService;
use App\Services\Billing\InstallmentService;

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

    /**
     * Cheapest real (non-quote) price in a line, in cents — null if none.
     * Sale-aware: a "From $X" that ignored a live sale would advertise a price
     * higher than the one we'd actually charge.
     */
    public static function fromPriceCents(string $categorySlug): ?int
    {
        $prices = [];
        foreach (self::plansForSlug($categorySlug) as $plan) {
            if ((int) $plan['price_cents'] <= 0) {
                continue; // quote-based
            }
            $sale = self::sale($plan);
            $prices[] = $sale ? $sale['now_cents'] : (int) $plan['price_cents'];
        }

        return $prices ? min($prices) : null;
    }

    /** True when any plan in the line is currently on sale. */
    public static function lineHasSale(string $categorySlug): bool
    {
        foreach (self::plansForSlug($categorySlug) as $plan) {
            if (self::sale($plan)) {
                return true;
            }
        }

        return false;
    }

    /** "/mo", "/yr" … for a recurring plan; empty string for one-off. */
    public static function suffix(array $plan): string
    {
        if (($plan['billing_type'] ?? '') !== 'recurring') {
            return '';
        }

        return '/' . substr((string) ($plan['interval'] ?: 'month'), 0, 2);
    }

    /**
     * What checkout will ACTUALLY bill for this plan on its default payment
     * plan, and over how many periods. Yearly hosting bills price × 12, so the
     * headline monthly price is NOT the charge.
     *
     * @return array{charge_cents:int, periods:int}
     */
    public static function defaultCharge(array $plan): array
    {
        $line = ($plan['category_id'] ?? null) ? ServiceCategory::find($plan['category_id']) : null;
        $installments = new InstallmentService();
        $chosen = $installments->resolvePlan($line['slug'] ?? null, null);
        $schedule = $installments->schedule((int) $plan['price_cents'], $chosen);

        return [
            'charge_cents' => (int) array_sum(array_column($schedule['rows'], 'amount_cents')),
            'periods'      => max(1, (int) $schedule['months']),
        ];
    }

    /**
     * The live sale on a plan, or null: ['sale' => row, 'amount_cents' => int,
     * 'was_cents' => int, 'now_cents' => int] — was/now are PER PERIOD, matching
     * the price the card displays.
     *
     * The discount is resolved against the real charge (see defaultCharge), the
     * same basis OrderController::place() uses. Resolving it against the monthly
     * headline instead would let a fixed-amount sale clamp differently in the
     * shop window than at the till — e.g. a $100-off sale on a $30/mo plan
     * advertising "$0.00/mo" while billing $260 for the year.
     */
    public static function sale(array $plan): ?array
    {
        // Quote-based plans have no price to discount.
        if ((int) ($plan['price_cents'] ?? 0) <= 0) {
            return null;
        }

        $discounts = new DiscountService();
        ['charge_cents' => $charge, 'periods' => $periods] = self::defaultCharge($plan);

        $sale = $discounts->saleForService($plan, $charge);
        if (! $sale) {
            return null;
        }

        $amount = $discounts->amountFor($sale, $charge);
        if ($amount <= 0) {
            return null;
        }

        // Spread the saving back over the periods the card quotes, so the
        // advertised per-period figure is exactly what will be charged.
        $nowPerPeriod = (int) round(($charge - $amount) / $periods);
        $wasPerPeriod = (int) $plan['price_cents'];
        if ($nowPerPeriod >= $wasPerPeriod) {
            return null; // nothing to advertise
        }

        return [
            'sale'          => $sale,
            'amount_cents'  => $wasPerPeriod - $nowPerPeriod, // saving per period
            'total_cents'   => $amount,                       // saving over the whole charge
            'was_cents'     => $wasPerPeriod,
            'now_cents'     => $nowPerPeriod,
            'periods'       => $periods,
            'charge_cents'  => $charge,
        ];
    }
}
