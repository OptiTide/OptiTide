<?php
/**
 * The price on a public plan card. Sale-aware: when a live automatic sale covers
 * the plan, the old price is struck through and the sale price shown, so the
 * advertised figure always matches what checkout will actually charge.
 *
 * Shared by the homepage packages and the per-service pages so a sale can never
 * show on one and not the other.
 *
 * Expects: $plan (a services row).
 *
 * Prices go through Currency::display() so the AUD/USD switcher keeps working on
 * both the was- and now-price.
 */
use App\Support\Catalog;
use App\Support\Currency;

$isQuote = (int) ($plan['price_cents'] ?? 0) === 0;
$sale = $isQuote ? null : Catalog::sale($plan);
?>
<?php if ($isQuote): ?>
    <span class="mk-price-num" style="font-size:1.4rem">Custom quote</span>
<?php elseif ($sale): ?>
    <span class="mk-price-was"><?= e(Currency::display($sale['was_cents'])) ?></span>
    <span class="mk-price-num mk-price-num--sale"><?= e(Currency::display($sale['now_cents'])) ?></span><span class="mk-price-per"><?= e(Catalog::suffix($plan)) ?></span>
    <span class="mk-sale-badge"><i class="bi bi-tag-fill"></i> <?= e($sale['sale']['name']) ?></span>
    <?php if (($plan['billing_type'] ?? '') === 'recurring'): ?>
        <?php // The biller renews at the full engagement price, so the discount
              // is first-period only. Saying "$600/mo" without this would be
              // misleading pricing — we'd advertise a rate we don't charge. ?>
        <span class="mk-price-note">first <?= e(substr((string) ($plan['interval'] ?: 'month'), 0, 5)) ?>, then <?= e(Currency::display($sale['was_cents'])) ?><?= e(Catalog::suffix($plan)) ?></span>
    <?php endif; ?>
<?php else: ?>
    <span class="mk-price-num"><?= e(Currency::display((int) $plan['price_cents'])) ?></span><span class="mk-price-per"><?= e(Catalog::suffix($plan)) ?></span>
<?php endif; ?>
