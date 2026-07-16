<?php
// Shared public site header: utility top bar + main nav (used on every public
// page so navigation is identical everywhere). Company details come from the
// admin Settings page (/admin/settings), which overrides config at boot.
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
            <?php if (! empty($company['phone'])): ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone-fill"></i> <?= e($company['phone']) ?></a><?php endif; ?>
            <a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope-fill"></i> <?= e($company['email']) ?></a>
            <span class="d-none d-lg-inline"><i class="bi bi-geo-alt-fill"></i> Australian Owned &amp; Operated</span>
        </div>
        <div class="mk-topbar-right">
            <span class="mk-ccy" title="Choose the currency prices are shown in">
                <?php foreach (\App\Support\Currency::supported() as $code): ?><a class="mk-ccy-link <?= $ccyNow === $code ? 'is-active' : '' ?>" href="<?= route('currency.set') ?>?c=<?= e($code) ?>&amp;return=<?= $ccyReturn ?>"><?= e($code) ?></a><?php endforeach; ?>
            </span>
            <span class="mk-topbar-hours d-none d-md-inline"><i class="bi bi-clock"></i> <?= e(config('company.hours')) ?></span>
            <a href="<?= $dashUrl ?>" class="mk-topbar-login"><i class="bi bi-person-circle"></i> <?= $isAuthed ? 'My Dashboard' : 'Client &amp; Staff Login' ?></a>
        </div>
    </div>
</div>

<!-- Main nav -->
<nav class="mk-nav">
    <div class="mk-container">
        <a href="/" class="mk-nav-brand" aria-label="<?= e($company['brand_name']) ?> home"><img class="brand-logo brand-logo--tagline" src="<?= asset('img/logo-tagline.png') ?>" alt="<?= e($company['brand_name']) ?> — Ride the Digital Tide"></a>
        <button class="mk-nav-toggle" type="button" aria-label="Menu" aria-expanded="false" onclick="var m=document.getElementById('mkNav');m.classList.toggle('open');this.setAttribute('aria-expanded',m.classList.contains('open'))"><i class="bi bi-list"></i></button>
        <div class="mk-nav-links" id="mkNav">
            <a href="/" class="mk-nav-link">Home</a>
            <div class="dropdown mk-nav-dd">
                <a href="<?= route('pages.services') ?>" class="mk-nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Services</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= route('pages.services') ?>"><i class="bi bi-grid me-1"></i> All Services</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach (\App\Controllers\PublicSite\PageController::serviceData() as $slug => $s): ?>
                        <li><a class="dropdown-item" href="/services/<?= e($slug) ?>"><i class="bi <?= e($s['icon']) ?> me-1"></i> <?= e($s['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="/#packages" class="mk-nav-link">Packages</a>
            <a href="<?= route('pages.about') ?>" class="mk-nav-link">About Us</a>
            <div class="dropdown mk-nav-dd">
                <a href="<?= route('blog.index') ?>" class="mk-nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Resources</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= route('blog.index') ?>">Blog</a></li>
                    <li><a class="dropdown-item" href="/#faq">FAQs</a></li>
                </ul>
            </div>
            <a href="<?= route('pages.contact') ?>" class="mk-nav-link">Contact</a>
            <a href="<?= $dashUrl ?>" class="mk-nav-link d-lg-none"><?= $isAuthed ? 'My Dashboard' : 'Login' ?></a>
            <a href="/#proposal" class="btn btn-brand mk-nav-cta">Get My Free Proposal</a>
        </div>
    </div>
</nav>
