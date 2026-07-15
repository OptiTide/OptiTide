<?php $company = config('company'); ?>
<footer class="mk-footer">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="brand-logo--chip d-inline-block mb-3"><img src="/assets/img/logo.png" alt="OptiTide" style="height:60px"></div>
                <p class="mk-footer-about">OptiTide is an Australian digital agency helping small businesses get found, look professional and grow online — web design, SEO, social media and hosting under one roof.</p>
                <div class="mk-footer-contact">
                    <?php if (! empty($company['email'])): ?><a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope"></i> <?= e($company['email']) ?></a><?php endif; ?>
                    <?php if (! empty($company['phone'])): ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></a><?php endif; ?>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Services</h5>
                <a href="/#services">Web Design</a>
                <a href="/#services">SEO</a>
                <a href="/#services">Social Media</a>
                <a href="/#services">Web Hosting</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Company</h5>
                <a href="/#why">Why OptiTide</a>
                <a href="/#process">Our Process</a>
                <a href="<?= route('blog.index') ?>">Blog</a>
                <a href="/#contact">Contact Us</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Legal</h5>
                <a href="<?= route('legal.terms') ?>">Terms of Service</a>
                <a href="<?= route('legal.privacy') ?>">Privacy Policy</a>
                <a href="<?= route('legal.refund') ?>">Refund Policy</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Get Started</h5>
                <a href="/#contact">Get a Free Quote</a>
                <a href="/#packages">View Packages</a>
                <a href="<?= route('register') ?>">Create Account</a>
            </div>
        </div>
        <div class="mk-footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= e($company['legal_name'] ?? 'OptiTide') ?><?= ! empty($company['abn']) ? ' · ABN ' . e($company['abn']) : '' ?></span>
            <span class="mk-footer-tagline">🇦🇺 Proudly Australian owned &amp; operated</span>
        </div>
    </div>
</footer>
