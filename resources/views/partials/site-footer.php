<?php $company = config('company'); ?>
<footer class="mk-footer">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="brand-logo--chip d-inline-block mb-3"><img src="/assets/img/logo.png" alt="OptiTide" style="height:60px"></div>
                <p class="mk-footer-about">Helping Australian businesses ride the digital tide with web design, SEO, marketing and hosting solutions that deliver real results.</p>
                <div class="mk-footer-social">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Services</h5>
                <a href="/#services">Web Design</a>
                <a href="/#services">SEO</a>
                <a href="/#services">Social Media Marketing</a>
                <a href="/#services">Managed Hosting</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Packages</h5>
                <a href="/#packages">Starter</a>
                <a href="/#packages">Growth</a>
                <a href="/#packages">Pro</a>
                <a href="/#packages">Business</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Company</h5>
                <a href="/#about">About Us</a>
                <a href="/#case-studies">Case Studies</a>
                <a href="<?= route('blog.index') ?>">Blog</a>
                <a href="/#contact">Contact Us</a>
            </div>
            <div class="col-6 col-lg-2">
                <h5>Get In Touch</h5>
                <?php if ($company['phone']): ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></a><?php endif; ?>
                <a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope"></i> <?= e($company['email']) ?></a>
                <a href="<?= route('legal.terms') ?>">Terms</a>
                <a href="<?= route('legal.privacy') ?>">Privacy Policy</a>
            </div>
        </div>
        <div class="mk-footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= e($company['legal_name'] ?? 'OptiTide') ?><?= ! empty($company['abn']) ? ' · ABN ' . e($company['abn']) : '' ?>. All rights reserved.</span>
            <span class="mk-footer-tagline">🇦🇺 Australian Owned &amp; Operated</span>
        </div>
    </div>
</footer>
