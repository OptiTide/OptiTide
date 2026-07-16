<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>Services</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">What We Do</span>
        <h1>Everything Your Business Needs Online</h1>
        <p class="mk-lead">Four core services that work together — so your website, your search rankings, your social presence and your hosting all pull in the same direction. One Australian team, fixed pricing, no lock-in contracts.</p>
        <div class="mk-hero-cta">
            <a href="/#proposal" class="btn btn-brand btn-lg">Get a Free Proposal</a>
            <a href="/#packages" class="btn btn-ghost btn-lg">View Pricing</a>
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

<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Not sure what you need?</h2>
            <p>Tell us about your business and we'll recommend the right mix — with honest advice and a clear quote.</p>
        </div>
        <a href="/contact" class="btn btn-brand btn-lg">Talk to Us <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
<?php $this->endSection(); ?>
