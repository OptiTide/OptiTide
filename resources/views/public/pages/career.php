<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\JobOpening;

$salary = JobOpening::salaryLabel($role);
$responsibilities = JobOpening::lines($role['responsibilities'] ?? null);
$requirements = JobOpening::lines($role['requirements'] ?? null);
$benefits = JobOpening::lines($role['benefits'] ?? null);
?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb">
            <a href="/">Home</a> <i class="bi bi-chevron-right"></i>
            <a href="<?= route('careers.index') ?>">Careers</a> <i class="bi bi-chevron-right"></i>
            <span><?= e($role['title']) ?></span>
        </nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)"><?= e($role['department'] ?: 'Open Role') ?></span>
        <h1><?= e($role['title']) ?></h1>
        <?php if (! empty($role['summary'])): ?>
            <p class="mk-lead"><?= e($role['summary']) ?></p>
        <?php endif; ?>
        <div class="mk-job-meta mk-job-meta--hero">
            <span><i class="bi bi-geo-alt"></i> <?= e($role['location']) ?></span>
            <span><i class="bi bi-clock"></i> <?= e(JobOpening::EMPLOYMENT_TYPES[$role['employment_type']] ?? '') ?></span>
            <span><i class="bi bi-laptop"></i> <?= e(JobOpening::WORKPLACE_TYPES[$role['workplace_type']] ?? '') ?></span>
            <?php if ($salary !== ''): ?><span class="mk-job-salary"><i class="bi bi-cash-coin"></i> <?= e($salary) ?></span><?php endif; ?>
            <?php if (! empty($role['closes_at'])): ?>
                <span><i class="bi bi-calendar-x"></i> Applications close <?= e(date('j M Y', strtotime((string) $role['closes_at']))) ?></span>
            <?php endif; ?>
        </div>
        <div class="mk-hero-cta">
            <a href="#apply" class="btn btn-brand btn-lg">Apply for This Role</a>
        </div>
    </div>
</header>

<section class="mk-section">
    <div class="mk-container">
        <!-- g-4, not g-5: .mk-container has 20px padding and a g-5 row's -24px
             margins would punch 4px past it on each side (horizontal scroll). -->
        <div class="row g-4">
            <div class="col-lg-7">
                <?php if (! empty($role['description'])): ?>
                    <h2 class="mk-h2">About the role</h2>
                    <div class="mk-job-body"><?= nl2br(e($role['description'])) ?></div>
                <?php endif; ?>

                <?php if ($responsibilities): ?>
                    <h2 class="mk-h2 mt-5">What you'll do</h2>
                    <ul class="mk-checklist">
                        <?php foreach ($responsibilities as $item): ?>
                            <li><i class="bi bi-check2-circle"></i> <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($requirements): ?>
                    <h2 class="mk-h2 mt-5">What we're after</h2>
                    <ul class="mk-checklist">
                        <?php foreach ($requirements as $item): ?>
                            <li><i class="bi bi-check2-circle"></i> <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($benefits): ?>
                    <h2 class="mk-h2 mt-5">What you'll get</h2>
                    <ul class="mk-checklist">
                        <?php foreach ($benefits as $item): ?>
                            <li><i class="bi bi-gift"></i> <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <aside class="mk-job-aside">
                    <h3>At a glance</h3>
                    <dl>
                        <?php if (! empty($role['department'])): ?>
                            <dt>Team</dt><dd><?= e($role['department']) ?></dd>
                        <?php endif; ?>
                        <dt>Location</dt><dd><?= e($role['location']) ?></dd>
                        <dt>Type</dt><dd><?= e(JobOpening::EMPLOYMENT_TYPES[$role['employment_type']] ?? '') ?></dd>
                        <dt>Workplace</dt><dd><?= e(JobOpening::WORKPLACE_TYPES[$role['workplace_type']] ?? '') ?></dd>
                        <?php if ($salary !== ''): ?>
                            <dt>Salary</dt><dd><?= e($salary) ?></dd>
                        <?php endif; ?>
                        <?php if (! empty($role['posted_at'])): ?>
                            <dt>Posted</dt><dd><?= e(date('j M Y', strtotime((string) $role['posted_at']))) ?></dd>
                        <?php endif; ?>
                    </dl>
                    <a href="#apply" class="btn btn-brand w-100">Apply Now</a>
                    <p class="small text-muted mt-3 mb-0">Questions before you apply? Email <a href="mailto:<?= e(config('company.email')) ?>"><?= e(config('company.email')) ?></a>.</p>
                </aside>
            </div>
        </div>
    </div>
</section>

<section class="mk-section mk-section--alt" id="apply">
    <div class="mk-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <span class="mk-eyebrow">Apply</span>
                    <h2 class="mk-h2">Apply for <?= e($role['title']) ?></h2>
                </div>
                <div class="mk-contact-card">
                    <?php $this->insert('public.pages.partials.apply-form', ['captcha' => $captcha, 'roleSlug' => $role['slug']]); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
