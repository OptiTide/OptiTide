<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>Services</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">What We Do</span>
        <h1>Everything Your Business Needs Online</h1>
        <p class="mk-lead">Four core services that work together — so your website, your search rankings, your social presence and your hosting all pull in the same direction. One Australian team, fixed pricing, no lock-in contracts.</p>
        <div class="mk-hero-cta">
            <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Get a Free Proposal</a>
            <a href="#pricing" class="btn btn-ghost btn-lg">View Pricing</a>
        </div>
    </div>
</header>

<section class="mk-section">
    <div class="mk-container">
        <div class="row g-4">
            <?php foreach ($services as $slug => $s): ?>
                <div class="col-md-6">
                    <article class="mk-service h-100">
                        <div class="mk-service-icon"><i class="bi <?= e($s['icon']) ?>"></i></div>
                        <h3><?= e($s['title']) ?></h3>
                        <?php if (! empty($s['from'])): ?>
                            <div class="mk-service-from">From <strong><?= e(\App\Support\Currency::display((int) $s['from'])) ?></strong></div>
                        <?php endif; ?>
                        <p><?= e($s['intro']) ?></p>
                        <ul>
                            <?php foreach (array_slice($s['includes'], 0, 4) as [$i, $t, $d]): ?><li><i class="bi bi-check2"></i> <?= e($t) ?></li><?php endforeach; ?>
                        </ul>
                        <a href="/services/<?= e($slug) ?>" class="mk-service-more">Learn more about <?= e($s['nav']) ?> <i class="bi bi-arrow-right"></i></a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Real pricing, straight from the admin catalogue — same grid as the homepage
     so the two can never disagree, and you can order right from here. -->
<section id="pricing" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Pricing</span>
            <h2 class="mk-h2">Simple, Transparent Pricing</h2>
            <p class="mk-lead mx-auto">Real prices, GST included, no lock-in contracts — the price you see is the price you pay.</p>
            <?php if (\App\Support\Currency::isConverted()): ?>
                <p class="small text-muted mx-auto mt-2" style="max-width:640px"><i class="bi bi-info-circle"></i> Prices shown in <?= e(\App\Support\Currency::current()) ?> are indicative, converted from AUD. Invoices are issued in AUD.</p>
            <?php endif; ?>
        </div>

        <?php $this->insert('public.pages.partials.pricing-grid', [
            'packages' => $packages, 'canOrder' => $canOrder, 'startUrl' => $startUrl,
        ]); ?>

        <p class="text-center text-muted small mt-4 mb-0">All prices in <?= e(config('company.currency', 'AUD')) ?> and include GST. Every plan comes with Australian support.</p>
    </div>
</section>

<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Not sure what you need?</h2>
            <p>Tell us about your business and we'll recommend the right mix — with honest advice and a clear quote.</p>
        </div>
        <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Talk to Us <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
<?php $this->endSection(); ?>
