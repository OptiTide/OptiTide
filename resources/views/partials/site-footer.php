<?php $company = config('company'); ?>
<footer class="mk-footer">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="brand-logo--chip d-inline-block mb-3"><img src="<?= asset('img/logo.png') ?>" alt="<?= e($company['brand_name']) ?>" style="height:60px"></div>
                <p class="mk-footer-about">Helping Australian businesses ride the digital tide with web design, SEO, marketing and hosting solutions that deliver real results.</p>
                <?php
                // Only render socials you've actually set in admin Settings.
                $socials = array_filter([
                    'facebook'  => $company['social']['facebook'] ?? null,
                    'instagram' => $company['social']['instagram'] ?? null,
                    'linkedin'  => $company['social']['linkedin'] ?? null,
                ]);
                ?>
                <?php if ($socials): ?>
                    <div class="mk-footer-social">
                        <?php foreach ($socials as $network => $url): ?>
                            <a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($network)) ?>"><i class="bi bi-<?= e($network) ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-6 col-lg-2 mk-footer-col">
                <h5>Services</h5>
                <a href="/services/web-design">Web Design</a>
                <a href="/services/seo">SEO</a>
                <a href="/services/social-media">Social Media</a>
                <a href="/services/hosting">Managed Hosting</a>
            </div>

            <div class="col-6 col-lg-2 mk-footer-col">
                <h5>Company</h5>
                <a href="/about">About Us</a>
                <a href="<?= route('pages.how-we-work') ?>">How We Work</a>
                <a href="/#packages">Packages</a>
                <a href="<?= route('blog.index') ?>">Blog</a>
                <a href="<?= route('careers.index') ?>">Careers</a>
                <a href="/contact">Contact Us</a>
            </div>

            <div class="col-6 col-lg-2 mk-footer-col">
                <h5>Legal</h5>
                <a href="<?= route('legal.terms') ?>">Terms of Service</a>
                <a href="<?= route('legal.privacy') ?>">Privacy Policy</a>
                <a href="<?= route('legal.refund') ?>">Refund Policy</a>
            </div>

            <div class="col-6 col-lg-2 mk-footer-col">
                <h5>Get In Touch</h5>
                <?php if (! empty($company['phone'])): ?>
                    <a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></a>
                <?php endif; ?>
                <a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope"></i> <?= e($company['email']) ?></a>
                <span class="mk-footer-note"><i class="bi bi-clock"></i> <?= e(config('company.hours')) ?></span>
                <span class="mk-footer-note"><i class="bi bi-geo-alt"></i> <?= e(\App\Support\Company::addressLineOr('Australia-wide')) ?></span>
            </div>
        </div>

        <div class="mk-footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= e($company['legal_name'] ?? 'OptiTide') ?><?= ! empty($company['abn']) ? ' · ABN ' . e($company['abn']) : '' ?>. All rights reserved.</span>
            <span class="mk-footer-tagline">🇦🇺 Australian Owned &amp; Operated</span>
        </div>
    </div>
</footer>
