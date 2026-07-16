<?php
/**
 * The real pricing grid: every active plan, grouped by service line, straight
 * from the admin catalogue. Shared by the homepage and /services so the two can
 * never show different prices.
 *
 * Expects: $packages (Catalog::grouped()), $canOrder (bool), $startUrl (string).
 *
 * Group sizes differ on purpose — Social Media has 1 plan, Hosting 2, Web Design
 * and SEO 3 — so the row centres itself rather than leaving a ragged gap.
 */
$packages = $packages ?? [];
$canOrder = $canOrder ?? false;
$startUrl = $startUrl ?? '/register';
?>
<?php foreach ($packages as $group): ?>
    <?php $n = count($group['plans']); ?>
    <div class="mk-price-group">
        <h3 class="mk-price-group-title"><?= e($group['line']['name']) ?></h3>
        <div class="row g-3 <?= $n < 3 ? 'justify-content-center' : '' ?>">
            <?php foreach ($group['plans'] as $i => $plan): ?>
                <?php
                $isQuote = (int) $plan['price_cents'] === 0;
                // Highlight the middle real tier in a 3-up group — the classic
                // "most popular" anchor. Never highlight a quote-only card.
                $featured = $n >= 3 && $i === 1 && ! $isQuote;
                ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="mk-price-card h-100 <?= $featured ? 'is-featured' : '' ?>">
                        <?php if ($featured): ?><div class="mk-price-flag">Most popular</div><?php endif; ?>
                        <div class="mk-price-name"><?= e($plan['name']) ?></div>
                        <?php if (! empty($plan['description'])): ?>
                            <div class="mk-price-blurb"><?= e($plan['description']) ?></div>
                        <?php endif; ?>
                        <div class="mk-price-amount">
                            <?php $this->insert('public.pages.partials.plan-price', ['plan' => $plan]); ?>
                        </div>
                        <div class="mk-price-terms">
                            <?= $plan['billing_type'] === 'recurring' ? 'Ongoing — cancel any time' : 'One-off project fee' ?>
                        </div>
                        <?php if ($isQuote): ?>
                            <a href="<?= route('pages.contact') ?>" class="btn btn-outline-brand w-100 mt-auto">Get a Quote</a>
                        <?php elseif ($canOrder): ?>
                            <a href="<?= route('portal.order.show', ['service' => $plan['id']]) ?>" class="btn btn-brand w-100 mt-auto">Order Now</a>
                        <?php else: ?>
                            <a href="<?= e($startUrl) ?>" class="btn btn-brand w-100 mt-auto">Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
