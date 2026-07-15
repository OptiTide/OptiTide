<?php
// Shared public site header (top bar + nav + marquee). Used on the homepage and
// all marketing/blog pages so navigation is identical everywhere. Anchors point
// at /#... so they work from any page (jump home, then to the section).
$company = config('company');
$isAuthed = \App\Core\Auth::check();
$dashUrl = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
?>
<!-- Top utility bar -->
<div class="mk-topbar">
    <div class="mk-container">
        <div class="mk-topbar-left">
            <a href="mailto:<?= e($company['email']) ?>"><i class="bi bi-envelope"></i> <?= e($company['email']) ?></a>
            <?php if ($company['phone']): ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $company['phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></a><?php endif; ?>
            <span class="d-none d-md-inline"><i class="bi bi-geo-alt"></i> Serving all of Australia</span>
        </div>
        <div class="mk-topbar-right">
            <?php $ccyNow = \App\Support\Currency::current(); $ccyReturn = rawurlencode($_SERVER['REQUEST_URI'] ?? '/'); ?>
            <span class="mk-ccy" title="Choose the currency prices are shown in">
                <i class="bi bi-currency-exchange"></i>
                <?php foreach (\App\Support\Currency::supported() as $code): ?><a class="mk-ccy-link <?= $ccyNow === $code ? 'is-active' : '' ?>" href="<?= route('currency.set') ?>?c=<?= e($code) ?>&amp;return=<?= $ccyReturn ?>"><?= e($code) ?></a><?php endforeach; ?>
            </span>
            <span class="mk-topbar-tag">Grow Online. Lead Always.</span>
        </div>
    </div>
</div>

<nav class="mk-nav">
    <div class="mk-container">
        <a href="/" aria-label="OptiTide home"><img class="brand-logo brand-logo--tagline" src="/assets/img/logo-tagline.png" alt="OptiTide — Grow Online. Lead Always."></a>
        <button class="mk-nav-toggle" type="button" aria-label="Menu" aria-expanded="false" onclick="var m=document.getElementById('mkNav');m.classList.toggle('open');this.setAttribute('aria-expanded',m.classList.contains('open'))"><i class="bi bi-list"></i></button>
        <div class="mk-nav-links" id="mkNav">
            <a href="/" class="mk-nav-link">Home</a>
            <a href="/#services" class="mk-nav-link">Services</a>
            <a href="/#packages" class="mk-nav-link">Packages</a>
            <a href="/#process" class="mk-nav-link">Process</a>
            <a href="/#why" class="mk-nav-link">Why Us</a>
            <a href="<?= route('blog.index') ?>" class="mk-nav-link">Blog</a>
            <a href="/#contact" class="mk-nav-link">Contact</a>
            <a href="<?= $dashUrl ?>" class="btn btn-brand btn-sm"><?= $isAuthed ? 'Dashboard' : 'Client Login' ?></a>
        </div>
    </div>
</nav>

<!-- Moving announcement banner -->
<div class="mk-marquee" aria-hidden="true">
    <div class="mk-marquee-track">
        <?php $msgs = ['Australian-owned & operated', 'Websites that win you customers', 'Get found on Google', 'Fixed pricing — no surprise bills', 'One team for web, SEO, social & hosting', 'Free, no-obligation quotes']; ?>
        <?php for ($i = 0; $i < 2; $i++): foreach ($msgs as $m): ?>
            <span class="mk-marquee-item"><i class="bi bi-stars"></i> <?= e($m) ?></span>
        <?php endforeach; endfor; ?>
    </div>
</div>
