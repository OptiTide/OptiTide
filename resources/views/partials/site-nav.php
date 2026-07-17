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
            <?php if (\App\Support\Features::enabled('currency_switcher')): ?>
            <span class="mk-ccy" title="Choose the currency prices are shown in">
                <?php foreach (\App\Support\Currency::supported() as $code): ?><a class="mk-ccy-link <?= $ccyNow === $code ? 'is-active' : '' ?>" href="<?= route('currency.set') ?>?c=<?= e($code) ?>&amp;return=<?= $ccyReturn ?>"><?= e($code) ?></a><?php endforeach; ?>
            </span>
            <?php endif; ?>
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
            <?php
            // Every item points at a real page. Homepage-anchor links (#packages,
            // #faq) used to sit here and threw you back to the homepage from any
            // other page — and "Packages" just duplicated "Services".
            $svcNav = \App\Controllers\PublicSite\PageController::serviceData();
            $here = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            $on = fn (string $path) => $here === $path || ($path !== '/' && str_starts_with($here, $path . '/'));
            ?>
            <a href="/" class="mk-nav-link <?= $on('/') ? 'is-active' : '' ?>">Home</a>
            <div class="dropdown mk-nav-dd">
                <a href="<?= route('pages.services') ?>" class="mk-nav-link dropdown-toggle <?= $on('/services') ? 'is-active' : '' ?>" data-bs-toggle="dropdown" role="button" aria-expanded="false">Services</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= route('pages.services') ?>"><i class="bi bi-grid me-1"></i> All Services &amp; Pricing</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($svcNav as $slug => $s): ?>
                        <li><a class="dropdown-item" href="/services/<?= e($slug) ?>"><i class="bi <?= e($s['icon']) ?> me-1"></i> <?= e($s['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="<?= route('pages.services') ?>#pricing" class="mk-nav-link">Pricing</a>
            <div class="dropdown mk-nav-dd">
                <a href="<?= route('pages.about') ?>" class="mk-nav-link dropdown-toggle <?= ($on('/about') || $on('/how-we-work') || $on('/careers')) ? 'is-active' : '' ?>" data-bs-toggle="dropdown" role="button" aria-expanded="false">Company</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= route('pages.about') ?>"><i class="bi bi-people me-1"></i> About Us</a></li>
                    <li><a class="dropdown-item" href="<?= route('pages.how-we-work') ?>"><i class="bi bi-clock-history me-1"></i> How We Work</a></li>
                    <?php if (\App\Support\Features::enabled('careers')): ?>
                    <li><a class="dropdown-item" href="<?= route('careers.index') ?>"><i class="bi bi-briefcase me-1"></i> Careers</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php if (\App\Support\Features::enabled('blog')): ?>
            <a href="<?= route('blog.index') ?>" class="mk-nav-link <?= $on('/blog') ? 'is-active' : '' ?>">Blog</a>
            <?php endif; ?>
            <a href="<?= route('pages.contact') ?>" class="mk-nav-link <?= $on('/contact') ? 'is-active' : '' ?>">Contact</a>
            <?php // Login lives in the MAIN nav, not just the top bar. It was only a
                  // 13px top-bar link plus a mobile-only nav item, so the way in for a
                  // client or staff member was the least visible thing on the page —
                  // and nobody looks in a utility bar for it. ?>
<?php   // "Dashboard", not "My Dashboard": the top bar already says "My Dashboard"
        // right above this, and the longer label made the signed-in row 71px wider
        // than the signed-out one — enough to overflow the container and jam the
        // links into the logo at EVERY desktop width. Keep this button's two
        // states close in width or the row only fits for logged-out visitors. ?>
            <a href="<?= $dashUrl ?>" class="btn btn-outline-brand mk-nav-login">
                <i class="bi bi-person-circle"></i> <?= $isAuthed ? 'Dashboard' : 'Login' ?>
            </a>
            <a href="<?= route('pages.contact') ?>" class="btn btn-brand mk-nav-cta">Get My Free Proposal</a>
        </div>
    </div>
</nav>
