<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>

<section class="mk-page-hero">
    <div class="mk-container">
        <h1 class="mk-page-title"><?= e($page['title']) ?></h1>
        <?php if (! empty($page['intro'])): ?>
            <p class="mk-lead text-white-50 mt-2" style="max-width:720px"><?= e($page['intro']) ?></p>
        <?php endif; ?>
        <div class="mt-4 d-flex flex-wrap gap-2">
            <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Get My Free Proposal</a>
            <a href="<?= route('pages.services') ?>" class="btn btn-outline-light btn-lg">See All Services &amp; Pricing</a>
        </div>
    </div>
</section>

<section class="mk-section">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php // Sanitised in the controller (DOM allow-list), never raw admin HTML. ?>
                <div class="mk-blog-body"><?= $body ?></div>

                <?php if ($faqs): ?>
                    <h2 class="h4 fw-bold mt-5 mb-3">Common questions</h2>
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $i => $f): ?>
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                        <?= e($f['q']) ?>
                                    </button>
                                </h3>
                                <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body"><?= e($f['a']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <?php if ($plans): ?>
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Pricing</div>
                        <div class="card-body">
                            <?php // Straight from the admin catalogue — a landing page can
                                  // never advertise a figure the checkout won't honour. ?>
                            <?php foreach ($plans as $plan): ?>
                                <div class="d-flex justify-content-between align-items-baseline border-bottom py-2">
                                    <span class="small fw-semibold"><?= e($plan['name']) ?></span>
                                    <span class="money fw-bold">
                                        <?= e((new \App\Support\Money((int) $plan['price_cents'], $plan['currency']))->format()) ?><?= e(\App\Support\Catalog::suffix($plan)) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-text mt-2">All prices in AUD and include GST. No lock-in contracts.</div>
                            <a href="<?= route('pages.contact') ?>" class="btn btn-brand w-100 mt-3">Ask for a Quote</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Talk to a human</div>
                        <p class="small text-muted mb-3">
                            We're <?= e(config('company.hours')) ?>. Tell us what you need and we'll come back with a
                            fixed-price proposal — no obligation.
                        </p>
                        <a href="mailto:<?= e(config('company.email')) ?>" class="btn btn-outline-brand w-100 btn-sm">
                            <i class="bi bi-envelope"></i> <?= e(config('company.email')) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
