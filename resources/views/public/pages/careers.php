<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\JobOpening;

$company = config('company');
$brand = $company['brand_name'];
?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>Careers</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">Careers</span>
        <h1>Come Build the Tide With Us</h1>
        <p class="mk-lead">We're a small Australian-owned digital agency doing web design, SEO, social media and hosting for small business. If you like real ownership over your work, honest clients and no corporate theatre, we'd like to hear from you.</p>
        <div class="mk-hero-cta">
            <?php if ($roles): ?>
                <a href="#roles" class="btn btn-brand btn-lg"><?= count($roles) ?> Open <?= count($roles) === 1 ? 'Role' : 'Roles' ?></a>
            <?php endif; ?>
            <a href="#apply" class="btn btn-ghost btn-lg">Send an Application</a>
        </div>
    </div>
</header>

<!-- Why work here -->
<section class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Why <?= e($brand) ?></span>
            <h2 class="mk-h2">What It's Like to Work Here</h2>
        </div>
        <div class="row g-4">
            <?php
            // Deliberately limited to facts about the business that are already
            // true and stated elsewhere on this site: Australian-owned, a small
            // team, four service lines, small-business clients. Do NOT add perks
            // (salary, leave, hours, "remote-first", culture promises) here —
            // per-role facts belong on the role itself, where the admin sets
            // them, and inventing benefits we haven't agreed to is a promise to
            // a candidate we may not be able to keep.
            $perks = [
                ['bi-people', 'A Small Team', 'You won\'t be a cog. On a team this size you own your work end to end and see it go live for real clients.'],
                ['bi-graph-up-arrow', 'Work That Shows', 'Small-business clients, where good work visibly moves the needle instead of disappearing into someone\'s budget.'],
                ['bi-grid', 'Four Service Lines', 'Web design, SEO, social media and hosting under one roof — so there\'s always an adjacent skill to pick up if you want it.'],
                ['bi-flag', 'Australian Owned', $brand . ' is an Australian-owned agency working with Australian businesses.'],
            ];
            foreach ($perks as [$icon, $title, $copy]): ?>
                <div class="col-md-6 col-lg-3">
                    <article class="mk-feature h-100">
                        <div class="mk-feature-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <h3><?= e($title) ?></h3>
                        <p><?= e($copy) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Open roles -->
<section class="mk-section mk-section--alt" id="roles">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Open Roles</span>
            <h2 class="mk-h2"><?= $roles ? 'We\'re Hiring' : 'No Open Roles Right Now' ?></h2>
        </div>

        <?php if ($roles): ?>
            <div class="mk-jobs">
                <?php foreach ($roles as $role): ?>
                    <?php $salary = JobOpening::salaryLabel($role); ?>
                    <a class="mk-job" href="<?= route('careers.show', ['slug' => $role['slug']]) ?>">
                        <div class="mk-job-main">
                            <h3 class="mk-job-title"><?= e($role['title']) ?></h3>
                            <?php if (! empty($role['summary'])): ?>
                                <p class="mk-job-summary"><?= e($role['summary']) ?></p>
                            <?php endif; ?>
                            <div class="mk-job-meta">
                                <?php if (! empty($role['department'])): ?>
                                    <span><i class="bi bi-diagram-3"></i> <?= e($role['department']) ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-geo-alt"></i> <?= e($role['location']) ?></span>
                                <span><i class="bi bi-clock"></i> <?= e(JobOpening::EMPLOYMENT_TYPES[$role['employment_type']] ?? '') ?></span>
                                <span><i class="bi bi-laptop"></i> <?= e(JobOpening::WORKPLACE_TYPES[$role['workplace_type']] ?? '') ?></span>
                                <?php if ($salary !== ''): ?>
                                    <span class="mk-job-salary"><i class="bi bi-cash-coin"></i> <?= e($salary) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="mk-job-go">View Role <i class="bi bi-arrow-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="mk-job-empty">
                <i class="bi bi-envelope-paper"></i>
                <h3>Nothing Advertised at the Moment</h3>
                <p>We're not actively advertising a role right now — but we're a small team that grows, and we keep good applications on file. If you're strong at web design, SEO, social media or hosting, introduce yourself below and we'll reach out when something opens up.</p>
                <a href="#apply" class="btn btn-brand">Send an Expression of Interest</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Application form -->
<section class="mk-section" id="apply">
    <div class="mk-container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <span class="mk-eyebrow">Apply</span>
                    <h2 class="mk-h2"><?= $roles ? 'General Application' : 'Introduce Yourself' ?></h2>
                    <p class="text-muted">
                        <?php if ($roles): ?>
                            Applying for a specific role? Open it above and apply there — it helps us route you to the right person. Otherwise, tell us what you do below.
                        <?php else: ?>
                            Tell us what you're great at and what you're looking for. We read every one.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mk-contact-card">
                    <?php $this->insert('public.pages.partials.apply-form', ['captcha' => $captcha, 'roleSlug' => null]); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Not Looking for a Job — But Need One Done?</h2>
            <p>We'd still love to hear from you. Tell us about your project and we'll come back with honest advice.</p>
        </div>
        <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Talk to Us <i class="bi bi-arrow-right"></i></a>
    </div>
</section>
<?php $this->endSection(); ?>
