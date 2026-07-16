<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>

<!-- Page hero -->
<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <a href="/services">Services</a> <i class="bi bi-chevron-right"></i> <span><?= e($service['nav']) ?></span></nav>
        <div class="mk-page-hero-icon"><i class="bi <?= e($service['icon']) ?>"></i></div>
        <h1><?= e($service['h1']) ?></h1>
        <p class="mk-lead"><?= e($service['intro']) ?></p>
        <div class="mk-hero-cta">
            <a href="/#proposal" class="btn btn-brand btn-lg">Get a Free Proposal</a>
            <a href="/#packages" class="btn btn-ghost btn-lg">View Pricing</a>
        </div>
    </div>
</header>

<!-- What's included -->
<section class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">What's Included</span>
            <h2 class="mk-h2">Everything you get with <?= e($service['title']) ?></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($service['includes'] as [$icon, $t, $d]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="mk-feature">
                        <div class="mk-feature-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <h3><?= e($t) ?></h3>
                        <p><?= e($d) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing (real plans from the admin catalogue) -->
<?php if ($plans): ?>
<section class="mk-section mk-section--alt" id="pricing">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Pricing</span>
            <h2 class="mk-h2"><?= e($service['nav']) ?> plans</h2>
            <p class="mk-lead mx-auto">Clear, fixed pricing — GST included, no lock-in contracts. Not sure which fits? <a href="/contact">Ask us</a> and we'll recommend one honestly.</p>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($plans as $plan): ?>
                <?php $isQuote = (int) $plan['price_cents'] === 0; ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="mk-price-card h-100">
                        <div class="mk-price-name"><?= e($plan['name']) ?></div>
                        <?php if (! empty($plan['description'])): ?>
                            <div class="mk-price-blurb"><?= e($plan['description']) ?></div>
                        <?php endif; ?>
                        <div class="mk-price-amount">
                            <?php $this->insert('public.pages.partials.plan-price', ['plan' => $plan]); ?>
                        </div>
                        <div class="mk-price-terms">
                            <?= $plan['billing_type'] === 'recurring' ? 'Ongoing monthly — cancel any time' : 'One-off project fee' ?>
                        </div>
                        <?php if ($isQuote): ?>
                            <a href="/contact" class="btn btn-outline-brand w-100 mt-auto">Get a Quote</a>
                        <?php elseif ($canOrder): ?>
                            <a href="<?= route('portal.order.show', ['service' => $plan['id']]) ?>" class="btn btn-brand w-100 mt-auto">Order Now</a>
                        <?php else: ?>
                            <a href="<?= e($startUrl) ?>" class="btn btn-brand w-100 mt-auto">Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-4 mb-0">All prices in <?= e(config('company.currency', 'AUD')) ?> and include GST. Managed hosting, SSL and support available with every plan.</p>
    </div>
</section>
<?php endif; ?>

<!-- Benefits -->
<section class="mk-section">
    <div class="mk-container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <span class="mk-eyebrow">Why it matters</span>
                <h2 class="mk-h2">What this means for your business</h2>
                <p class="mk-lead">We don't do busywork — everything is aimed at real outcomes you can measure.</p>
            </div>
            <div class="col-lg-6">
                <ul class="mk-checklist">
                    <?php foreach ($service['benefits'] as $b): ?>
                        <li><i class="bi bi-check-circle-fill"></i> <?= e($b) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Other services -->
<section class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5"><h2 class="mk-h2">Explore our other services</h2></div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($others as $oslug => $o): ?>
                <div class="col-sm-6 col-lg-3">
                    <a href="/services/<?= e($oslug) ?>" class="mk-service-link">
                        <i class="bi <?= e($o['icon']) ?>"></i>
                        <span><?= e($o['nav']) ?></span>
                        <i class="bi bi-arrow-right mk-service-link-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Ready to get started?</h2>
            <p>Tell us about your business and we'll send a free, no-obligation proposal within 24 hours.</p>
        </div>
        <a href="/#proposal" class="btn btn-brand btn-lg">Get My Free Proposal <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
<?php $this->endSection(); ?>
