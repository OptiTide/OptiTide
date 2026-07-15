<?php
/**
 * Public, indexable layout for marketing/content pages (blog, etc.). Unlike the
 * app's partials/head (noindex), this emits full SEO meta + structured data.
 *
 * Expected vars: $seoTitle, $seoDescription, $canonical.
 * Optional: $ogImage, $ogType, $jsonLd (array), $bodyClass.
 */
$appUrl   = rtrim(config('app.url'), '/');
$brand    = config('app.brand.accent', '#FF6A00');
$seoTitle = $seoTitle ?? config('app.name', 'OptiTide');
$seoDescription = $seoDescription ?? '';
$canonical = $canonical ?? ($appUrl . '/');
$ogImage  = $ogImage ?? ($appUrl . '/assets/img/favicon.png');
$ogType   = $ogType ?? 'website';
$isAuthed = \App\Core\Auth::check();
$dashUrl  = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
$company  = config('company');
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seoTitle) ?></title>
<meta name="description" content="<?= e($seoDescription) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="<?= e($brand) ?>">
<meta name="geo.region" content="AU">
<meta name="geo.placename" content="Australia">

<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="OptiTide">
<meta property="og:locale" content="en_AU">
<meta property="og:title" content="<?= e($seoTitle) ?>">
<meta property="og:description" content="<?= e($seoDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seoTitle) ?>">
<meta name="twitter:description" content="<?= e($seoDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<link rel="icon" href="/assets/img/favicon.png" sizes="any">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">
<style>:root{--brand: <?= e($brand) ?>; --brand-dark: <?= e(config('app.brand.accent_dark', '#E85F00')) ?>;}</style>
<?php if (! empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php $this->insert('partials.analytics'); ?>
<?php $this->insert('partials.pwa'); ?>
</head>
<body class="mk">

<?php $this->insert('partials.site-nav'); ?>

<main>
    <?= $this->yield('content') ?>
</main>

<footer class="mk-footer">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="brand-logo--chip d-inline-block mb-3"><img src="/assets/img/logo.png" alt="OptiTide" style="height:40px"></div>
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
        <div class="mk-footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($company['legal_name'] ?? 'OptiTide') ?><?= ! empty($company['abn']) ? ' · ABN ' . e($company['abn']) : '' ?></span>
            <span class="mk-footer-tagline">Grow Online. Lead Always.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->insert('partials.chat-widget'); ?>
</body>
</html>
