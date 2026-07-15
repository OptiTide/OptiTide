<?php
// Shared public site header: utility top bar + main nav (used on every public
// page so navigation is identical everywhere). Section links point at /#... so
// they work from any page.
$company = config('company');
$isAuthed = \App\Core\Auth::check();
$dashUrl = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
$ccyNow = \App\Support\Currency::current();
$ccyReturn = rawurlencode($_SERVER['REQUEST_URI'] ?? '/');
?>
<!-- Utility top bar -->
<div class="mk-topbar">
    <div class="mk-container">
        <div class="mk-topbar-left">
            <?php if ($company['phone']): ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone-fill"></i> <?= e($company['phone']) ?></a><?php endif; ?>
            <a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope-fill"></i> <?= e($company['email']) ?></a>
            <span class="d-none d-lg-inline"><i class="bi bi-geo-alt-fill"></i> Australian Owned &amp; Operated</span>
        </div>
        <div class="mk-topbar-right">
            <span class="mk-ccy" title="Choose the currency prices are shown in">
                <?php foreach (\App\Support\Currency::supported() as $code): ?><a class="mk-ccy-link <?= $ccyNow === $code ? 'is-active' : '' ?>" href="<?= route('currency.set') ?>?c=<?= e($code) ?>&amp;return=<?= $ccyReturn ?>"><?= e($code) ?></a><?php endforeach; ?>
            </span>
            <span class="mk-topbar-hours"><i class="bi bi-clock"></i> Mon – Fri 9am – 5pm AEST</span>
            <span class="mk-flag" aria-hidden="true">🇦🇺</span>
        </div>
    </div>
</div>

<!-- Main nav -->
<nav class="mk-nav">
    <div class="mk-container">
        <a href="/" class="mk-nav-brand" aria-label="OptiTide home"><img class="brand-logo brand-logo--tagline" src="/assets/img/logo-tagline.png" alt="OptiTide — Ride the Digital Tide"></a>
        <button class="mk-nav-toggle" type="button" aria-label="Menu" aria-expanded="false" onclick="var m=document.getElementById('mkNav');m.classList.toggle('open');this.setAttribute('aria-expanded',m.classList.contains('open'))"><i class="bi bi-list"></i></button>
        <div class="mk-nav-links" id="mkNav">
            <a href="/" class="mk-nav-link">Home</a>
            <div class="dropdown mk-nav-dd">
                <a href="/#services" class="mk-nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Services</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/#services">Web Design &amp; Development</a></li>
                    <li><a class="dropdown-item" href="/#services">Search Engine Optimisation</a></li>
                    <li><a class="dropdown-item" href="/#services">Social Media Marketing</a></li>
                    <li><a class="dropdown-item" href="/#services">Managed Hosting</a></li>
                </ul>
            </div>
            <a href="/#packages" class="mk-nav-link">Packages</a>
            <a href="/#about" class="mk-nav-link">About Us</a>
            <a href="/#case-studies" class="mk-nav-link">Case Studies</a>
            <div class="dropdown mk-nav-dd">
                <a href="<?= route('blog.index') ?>" class="mk-nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Resources</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= route('blog.index') ?>">Blog</a></li>
                    <li><a class="dropdown-item" href="/#faq">FAQs</a></li>
                </ul>
            </div>
            <a href="/#contact" class="mk-nav-link">Contact</a>
            <?php if ($isAuthed): ?><a href="<?= $dashUrl ?>" class="mk-nav-link">Dashboard</a><?php endif; ?>
            <a href="/#proposal" class="btn btn-brand mk-nav-cta">Get My Free Proposal</a>
        </div>
    </div>
</nav>
