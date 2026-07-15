<?php $this->extends('layouts.public'); ?>
<?php $this->section('content'); ?>

<section class="hero text-center">
    <div class="mx-auto" style="max-width:680px">
        <span class="badge badge-soft mb-3">Australian digital agency</span>
        <h1 class="display-5 mb-3">Billing &amp; client management for <span class="text-brand">OptiTide</span></h1>
        <p class="lead text-muted mb-4">
            Web design, SEO, social media and hosting — invoiced properly, paid easily,
            with GST-ready tax invoices and a tidy client portal.
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <a href="<?= route('login') ?>" class="btn btn-brand btn-lg">Client login</a>
            <a href="<?= route('register') ?>" class="btn btn-outline-brand btn-lg">Create account</a>
        </div>
    </div>
</section>

<?php if ($categories): ?>
    <section class="pb-5">
        <div class="row g-3">
            <?php
            $icons = ['web-design' => 'bi-palette', 'seo' => 'bi-graph-up-arrow', 'smm' => 'bi-megaphone', 'hosting' => 'bi-hdd-network'];
            foreach ($categories as $category):
                $icon = $icons[$category['slug']] ?? 'bi-stars';
                ?>
                <div class="col-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="stat-icon mb-3"><i class="bi <?= e($icon) ?>"></i></div>
                            <h2 class="h6 mb-1"><?= e($category['name']) ?></h2>
                            <p class="text-muted small mb-0"><?= e($category['description'] ?: 'Managed by OptiTide.') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php $this->endSection(); ?>
