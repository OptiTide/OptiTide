<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php $company = config('company'); ?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>Contact</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">Get In Touch</span>
        <h1>Let's Talk About Your Project</h1>
        <p class="mk-lead">Tell us a little about your business and what you're after. We'll get back to you with honest advice and a clear quote — no pressure, no obligation.</p>
    </div>
</header>

<section class="mk-section" id="contact">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-5">
                <span class="mk-eyebrow">Contact Details</span>
                <h2 class="mk-h2">Reach Us Directly</h2>
                <ul class="mk-contact-info list-unstyled mt-3">
                    <?php if (! empty($company['phone'])): ?>
                        <li><i class="bi bi-telephone"></i> <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>" class="text-decoration-none"><?= e($company['phone']) ?></a></li>
                    <?php endif; ?>
                    <li><i class="bi bi-envelope"></i> <a href="mailto:<?= e($company['email']) ?>" class="text-decoration-none"><?= e($company['email']) ?></a></li>
                    <li><i class="bi bi-clock"></i> <?= e(config('company.hours')) ?></li>
                    <?php if (\App\Support\Company::hasAddress()): ?>
                        <li><i class="bi bi-geo-alt"></i> <?= e(\App\Support\Company::publicLocality()) ?></li>
                    <?php else: ?>
                        <li><i class="bi bi-geo-alt"></i> Serving businesses Australia-wide</li>
                    <?php endif; ?>
                    <?php if (! empty($company['abn'])): ?><li><i class="bi bi-building"></i> <?= e($company['legal_name']) ?> · ABN <?= e($company['abn']) ?></li><?php endif; ?>
                </ul>
                <div class="mk-chat-card mt-4">
                    <div>
                        <div class="fw-bold"><i class="bi bi-chat-dots text-brand"></i> Prefer to chat?</div>
                        <div class="small text-muted">Use the live chat in the corner — our assistant answers instantly and a human can jump in.</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="mk-contact-card">
                    <?php if (session('success')): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= e(session('success')) ?></div>
                    <?php endif; ?>
                    <?php if (session('error')): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e(session('error')) ?></div>
                    <?php endif; ?>
                    <?php if (errors()): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please check the form and try again.</div><?php endif; ?>
                    <form method="post" action="<?= route('contact.submit') ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="return" value="/contact#contact">
                        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="p_name">Full Name</label><input id="p_name" type="text" name="name" value="<?= e(old('name')) ?>" autocomplete="name" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" placeholder="Your full name" required></div>
                            <div class="col-md-6"><label class="form-label" for="p_email">Email Address</label><input id="p_email" type="email" name="email" value="<?= e(old('email')) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="p_phone">Phone <span class="text-muted small">(optional)</span></label><input id="p_phone" type="text" name="phone" value="<?= e(old('phone')) ?>" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label" for="p_service">I'm interested in</label>
                                <select id="p_service" name="service" class="form-select">
                                    <?php // From the real service map — this hardcoded "Managed Hosting",
                                          // a line we don't have (we sell Unmanaged AND Managed). ?>
                                    <?php foreach (\App\Controllers\PublicSite\PageController::serviceData() as $svcOpt): ?>
                                        <option <?= old('service') === $svcOpt['nav'] ? 'selected' : '' ?>><?= e($svcOpt['nav']) ?></option>
                                    <?php endforeach; ?>
                                    <option <?= old('service') === 'Not sure yet' ? 'selected' : '' ?>>Not sure yet</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label" for="p_msg">How can we help?</label><textarea id="p_msg" name="message" rows="5" class="form-control <?= has_error('message') ? 'is-invalid' : '' ?>" required><?= e(old('message')) ?></textarea></div>
                            <div class="col-12">
                                <label class="form-label" for="p_captcha">Quick check: <?= e($captcha ?? 'What is 3 + 4?') ?> <span class="text-danger">*</span></label>
                                <input id="p_captcha" type="text" name="captcha" inputmode="numeric" autocomplete="off" class="form-control <?= has_error('captcha') ? 'is-invalid' : '' ?>" style="max-width:180px" required>
                                <?php if (error('captcha')): ?><div class="invalid-feedback d-block"><?= e(error('captcha')) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-brand btn-lg mt-3">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
