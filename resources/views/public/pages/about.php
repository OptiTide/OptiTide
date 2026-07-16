<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php $company = config('company'); ?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>About Us</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">About <?= e(config('company.brand_name')) ?></span>
        <h1>Your Australian Digital Growth Partner</h1>
        <p class="mk-lead">We help small and growing Australian businesses get found, look professional and grow online — with web design, SEO, social media and hosting under one roof, honest advice and fixed pricing.</p>
    </div>
</header>

<section class="mk-section">
    <div class="mk-container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                <span class="mk-eyebrow">Who We Are</span>
                <h2 class="mk-h2">One team for everything online</h2>
                <p class="mk-lead">Too many businesses juggle a web designer, an SEO person, a social media freelancer and a hosting company that all point fingers at each other. We bring it all together — so your website, your search rankings, your social presence and your hosting work as one.</p>
                <p class="text-muted">We're small enough to give you real attention and experienced enough to deliver. No jargon, no lock-in contracts, and no surprise bills — just a genuine partner in your growth.</p>
            </div>
            <div class="col-lg-6">
                <span class="mk-eyebrow">What We Stand For</span>
                <h2 class="mk-h2">How we work</h2>
                <div class="row g-3 mt-1">
                    <?php
                    $values = [
                        ['bi-geo-alt', 'Australian-owned', 'A local team who understand the Australian market, with support in your timezone.'],
                        ['bi-cash-coin', 'Transparent pricing', 'A clear, fixed quote before we start — the price we quote is the price you pay.'],
                        ['bi-unlock', 'No lock-in contracts', 'We earn your business every month with results and service, not paperwork.'],
                        ['bi-graph-up-arrow', 'Results-focused', 'Everything we do is aimed at real outcomes — more traffic, more leads, more customers.'],
                    ];
                    foreach ($values as [$icon, $t, $d]): ?>
                        <div class="col-sm-6">
                            <div class="mk-benefit">
                                <i class="bi <?= e($icon) ?>"></i>
                                <div><h4><?= e($t) ?></h4><p><?= e($d) ?></p></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mk-section mk-section--alt">
    <div class="mk-container text-center">
        <span class="mk-eyebrow">By the numbers</span>
        <h2 class="mk-h2">Built for Australian business</h2>
        <div class="row g-4 mt-2 justify-content-center">
            <div class="col-6 col-lg-3"><div class="mk-stat"><div class="n">4-in-1</div><div class="l">Web, SEO, social &amp; hosting</div></div></div>
            <div class="col-6 col-lg-3"><div class="mk-stat"><div class="n">100%</div><div class="l">Australian owned &amp; operated</div></div></div>
            <div class="col-6 col-lg-3"><div class="mk-stat"><div class="n">Fixed</div><div class="l">Upfront pricing, GST included</div></div></div>
            <div class="col-6 col-lg-3"><div class="mk-stat"><div class="n">No</div><div class="l">Lock-in contracts</div></div></div>
        </div>
        <?php if (! empty($company['abn'])): ?><p class="text-muted small mt-4 mb-0"><?= e($company['legal_name']) ?> · ABN <?= e($company['abn']) ?> · Serving businesses Australia-wide</p><?php endif; ?>
    </div>
</section>

<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Let's grow your business online</h2>
            <p>Get a free, no-obligation proposal and see how we can help you attract more customers.</p>
        </div>
        <a href="/#proposal" class="btn btn-brand btn-lg">Get My Free Proposal <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
<?php $this->endSection(); ?>
