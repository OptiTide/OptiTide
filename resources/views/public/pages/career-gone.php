<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php
// Served with a 404 so search engines drop the stale URL, but with a useful page
// rather than an error wall — job-board links outlive the role they point to.
// $canonical is set by the controller: the layout is rendered from the
// controller's data, so assigning it here would have no effect.
?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb">
            <a href="/">Home</a> <i class="bi bi-chevron-right"></i>
            <a href="<?= route('careers.index') ?>">Careers</a> <i class="bi bi-chevron-right"></i>
            <span>Role Closed</span>
        </nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">Careers</span>
        <h1>That Role Has Closed</h1>
        <p class="mk-lead">Thanks for your interest — this one's been filled or is no longer being advertised. Here's what else is open.</p>
    </div>
</header>

<section class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Open Roles</span>
            <h2 class="mk-h2"><?= $roles ? 'Still Open' : 'Nothing Open Right Now' ?></h2>
        </div>
        <?php if ($roles): ?>
            <div class="mk-jobs">
                <?php foreach ($roles as $r): ?>
                    <a class="mk-job" href="<?= route('careers.show', ['slug' => $r['slug']]) ?>">
                        <div class="mk-job-main">
                            <h3 class="mk-job-title"><?= e($r['title']) ?></h3>
                            <div class="mk-job-meta">
                                <span><i class="bi bi-geo-alt"></i> <?= e($r['location']) ?></span>
                                <span><i class="bi bi-clock"></i> <?= e(\App\Models\JobOpening::EMPLOYMENT_TYPES[$r['employment_type']] ?? '') ?></span>
                            </div>
                        </div>
                        <span class="mk-job-go">View Role <i class="bi bi-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mk-job-empty">
                <i class="bi bi-envelope-paper"></i>
                <h3>No Roles Open Right Now</h3>
                <p>We keep good applications on file — send us an expression of interest and we'll reach out when something opens up.</p>
                <a href="<?= route('careers.index') ?>#apply" class="btn btn-brand">Send an Expression of Interest</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $this->endSection(); ?>
