<?php

namespace App\Services\Billing;

use App\Core\Database;
use App\Models\Discount;
use App\Models\DiscountRedemption;
use App\Models\Service;
use App\Support\Money;

/**
 * The single place that decides whether a discount applies and what it's worth.
 *
 * Money rules that must not be broken:
 *  - Amounts are integer minor units, always. Percent values are basis points.
 *  - A discount can never exceed the amount being discounted, so a total can
 *    never go negative (a negative invoice would be a refund we never agreed to).
 *  - GST is INCLUSIVE, so the discount comes off the GST-inclusive figure and
 *    GST is re-derived from what's left (see InvoiceService::recomputeTotals).
 *    Never discount the ex-GST subtotal — that would overstate GST.
 *
 * Redemption is recorded on application, and usage limits are claimed with a
 * conditional UPDATE so two simultaneous checkouts can't both take the last use.
 */
final class DiscountService
{
    /**
     * Work out the discount for a single service purchase.
     *
     * $chargeCents is what's ACTUALLY being billed, which is not always the
     * service price — yearly hosting bills price × 12. Percentages and minimum
     * spend must both apply to the real charge, or "20% off" a $600 yearly order
     * would take off $10 instead of $120.
     *
     * @return array{discount:?array, amount_cents:int, error:?string}
     */
    public function resolveForService(?string $code, array $service, int|string|null $clientId, ?int $chargeCents = null): array
    {
        $priceCents = $chargeCents ?? (int) $service['price_cents'];
        $code = Discount::normaliseCode($code);

        // No code typed: fall back to any automatic sale that covers this
        // service, so a sale still applies to someone who never saw the banner.
        if ($code === null) {
            $sale = $this->saleForService($service, $priceCents);

            return $sale
                ? ['discount' => $sale, 'amount_cents' => $this->amountFor($sale, $priceCents), 'error' => null]
                : ['discount' => null, 'amount_cents' => 0, 'error' => null];
        }

        $discount = Discount::findByCode($code);
        if (! $discount) {
            return ['discount' => null, 'amount_cents' => 0, 'error' => 'That code isn\'t valid. Check the spelling, or leave it blank.'];
        }

        if ($error = $this->reasonUnusable($discount, $service, $priceCents, $clientId)) {
            return ['discount' => null, 'amount_cents' => 0, 'error' => $error];
        }

        $amount = $this->amountFor($discount, $priceCents);
        if ($amount <= 0) {
            return ['discount' => null, 'amount_cents' => 0, 'error' => 'That code doesn\'t reduce this price.'];
        }

        // If an automatic sale beats the typed code, give the client the better
        // deal rather than silently charging them more for using a code.
        $sale = $this->saleForService($service, $priceCents);
        if ($sale && $this->amountFor($sale, $priceCents) > $amount) {
            return ['discount' => $sale, 'amount_cents' => $this->amountFor($sale, $priceCents), 'error' => null];
        }

        return ['discount' => $discount, 'amount_cents' => $amount, 'error' => null];
    }

    /** Why this discount can't be used right now, or null if it can. */
    public function reasonUnusable(array $discount, ?array $service, int $amountCents, int|string|null $clientId): ?string
    {
        if (! $discount['active']) {
            return 'That code is no longer available.';
        }
        if (! Discount::inWindow($discount)) {
            $today = today();
            $from = substr((string) ($discount['starts_at'] ?? ''), 0, 10);

            return $from !== '' && $today < $from
                ? 'That code isn\'t active yet.'
                : 'That code has expired.';
        }
        if (! Discount::hasUsesLeft($discount)) {
            return 'That code has been fully redeemed.';
        }
        if (! empty($discount['client_id']) && (string) $discount['client_id'] !== (string) $clientId) {
            // Don't reveal that it's someone else's code.
            return 'That code isn\'t valid for this account.';
        }
        if ($service !== null && ! $this->coversService($discount, $service)) {
            return 'That code doesn\'t apply to this package.';
        }
        $min = $discount['min_spend_cents'] ?? null;
        if ($min !== null && $min !== '' && $amountCents < (int) $min) {
            $minLabel = (new Money((int) $min, $discount['currency'] ?: 'AUD'))->format();

            return 'That code needs a minimum spend of ' . $minLabel . '.';
        }
        if ($clientId !== null && ! $this->hasPerClientUsesLeft($discount, $clientId)) {
            return 'You\'ve already used that code.';
        }

        return null;
    }

    /** Does this discount's scope cover the given service row? */
    public function coversService(array $discount, array $service): bool
    {
        return match ($discount['scope']) {
            Discount::SCOPE_SERVICE  => (string) $discount['service_id'] === (string) $service['id'],
            Discount::SCOPE_CATEGORY => (string) $discount['category_id'] === (string) ($service['category_id'] ?? ''),
            default                  => true,
        };
    }

    /**
     * The best live automatic sale for a service, or null. Best = largest saving
     * on this price, so overlapping sales never shortchange the client.
     */
    public function saleForService(array $service, ?int $chargeCents = null): ?array
    {
        $price = $chargeCents ?? (int) $service['price_cents'];
        $best = null;
        $bestAmount = 0;

        foreach (Discount::liveSales() as $sale) {
            if (! empty($sale['client_id'])) {
                continue; // a client-specific deal is not a public sale
            }
            if (! $this->coversService($sale, $service)) {
                continue;
            }
            $min = $sale['min_spend_cents'] ?? null;
            if ($min !== null && $min !== '' && $price < (int) $min) {
                continue;
            }
            $amount = $this->amountFor($sale, $price);
            if ($amount > $bestAmount) {
                $best = $sale;
                $bestAmount = $amount;
            }
        }

        return $best;
    }

    /**
     * What this discount is worth against an amount, in cents. Never more than
     * the amount itself — a discount cannot make a total negative.
     */
    public function amountFor(array $discount, int $amountCents): int
    {
        if ($amountCents <= 0) {
            return 0;
        }

        $raw = $discount['type'] === Discount::TYPE_PERCENT
            ? (int) round($amountCents * (int) $discount['value'] / 10000)
            : (int) $discount['value'];

        return max(0, min($raw, $amountCents));
    }

    /**
     * Split a discount across several invoice amounts in proportion to each,
     * with the last absorbing the rounding remainder — mirroring how
     * InstallmentService::schedule() allocates, so the parts always sum to
     * exactly the discount and no part exceeds its own invoice.
     *
     * @param  array<int,int> $amounts invoice amounts in cents
     * @return array<int,int> discount per invoice, same order
     */
    public function allocate(int $discountCents, array $amounts): array
    {
        $total = array_sum($amounts);
        if ($total <= 0 || $discountCents <= 0) {
            return array_fill(0, count($amounts), 0);
        }
        $discountCents = min($discountCents, $total);

        $out = [];
        $allocated = 0;
        $n = count($amounts);
        foreach ($amounts as $i => $amount) {
            $isLast = $i === $n - 1;
            $part = $isLast
                ? ($discountCents - $allocated)
                : (int) round($discountCents * ($amount / $total));
            // Never let a part exceed its own invoice (would go negative).
            $part = max(0, min($part, $amount));
            $allocated += $part;
            $out[] = $part;
        }

        return $out;
    }

    /**
     * Claim a use and record the redemption. The usage counter is incremented
     * with a conditional UPDATE (compare-and-swap on uses < max_uses), so two
     * checkouts racing for the last use can't both win.
     *
     * @return bool false when the last use was taken by someone else first
     */
    public function redeem(array $discount, int $amountCents, int|string|null $clientId, int|string|null $invoiceId): bool
    {
        $db = Database::instance();

        $max = $discount['max_uses'] ?? null;
        if ($max === null || $max === '') {
            $db->affecting('UPDATE discounts SET uses = uses + 1 WHERE id = ?', [$discount['id']]);
        } else {
            // Compare-and-swap: the UPDATE only matches while a use remains, so
            // two checkouts racing for the last one can't both succeed.
            // affecting() returns the row count — statement() would report
            // success even when nothing matched.
            $claimed = $db->affecting(
                'UPDATE discounts SET uses = uses + 1 WHERE id = ? AND uses < ?',
                [$discount['id'], (int) $max]
            );
            if ($claimed < 1) {
                return false;
            }
        }

        DiscountRedemption::create([
            'discount_id'  => $discount['id'],
            'client_id'    => $clientId ?: null,
            'invoice_id'   => $invoiceId ?: null,
            'amount_cents' => $amountCents,
            'currency'     => $discount['currency'] ?: config('company.currency', 'AUD'),
        ]);

        return true;
    }

    private function hasPerClientUsesLeft(array $discount, int|string $clientId): bool
    {
        $limit = $discount['max_uses_per_client'] ?? null;
        if ($limit === null || $limit === '') {
            return true;
        }

        $used = count(DiscountRedemption::query()
            ->where('discount_id', $discount['id'])
            ->where('client_id', $clientId)
            ->get());

        return $used < (int) $limit;
    }
}
