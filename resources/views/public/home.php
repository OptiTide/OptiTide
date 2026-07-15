<?php
$company = config('company');
$appUrl = rtrim(config('app.url'), '/');
$brand = config('app.brand.accent', '#7c3aed');
$title = 'OptiTide — Web Design, SEO, Social Media & Hosting in Australia';
$description = 'OptiTide is an Australian digital agency delivering web design, SEO, social media marketing and managed hosting for small business — all under one roof, with clear pricing and GST-ready tax invoices.';
$ogImage = $appUrl . '/assets/img/optitide-mark.svg';

$services = [
    ['bi-palette', 'Web Design & Development', 'Fast, modern, mobile-first websites built to convert visitors into customers and to rank well from day one. We design around your brand and your goals — not a template.', ['Custom responsive design', 'SEO-friendly, fast-loading build', 'Easy-to-edit content management', 'E-commerce & bookings ready', 'Ongoing care & updates']],
    ['bi-graph-up-arrow', 'Search Engine Optimisation (SEO)', 'Get found by the people already searching for what you do. We combine technical SEO, on-page optimisation, local search and content to grow qualified organic traffic over time.', ['Full technical SEO audit', 'On-page & content optimisation', 'Local SEO & Google Business Profile', 'Keyword & content strategy', 'Transparent monthly reporting']],
    ['bi-megaphone', 'Social Media Marketing', 'Show up consistently where your audience already is. We plan, create and manage on-brand content that builds awareness, engagement and trust across your channels.', ['Monthly content calendar', 'On-brand graphics & copy', 'Scheduling & publishing', 'Community management', 'Performance reporting']],
    ['bi-hdd-network', 'Managed Web Hosting', 'Reliable, secure, fully managed hosting so your website is always fast, safe and online. We handle the servers, security and backups — you focus on your business.', ['Fast, secure managed hosting', 'Free SSL & security hardening', 'Automated daily backups', 'Uptime & SSL monitoring', 'Priority Australian support']],
];
// Drop PNGs named service-<slug>.png in public/assets/img to use your own icons.
$serviceSlugs = ['web-design', 'seo', 'social-media', 'hosting'];

$process = [
    ['Discover', 'We start by understanding your business, your customers and your goals — so everything we build has a purpose.'],
    ['Plan', 'You get a clear strategy and a fixed, transparent proposal. No jargon, no surprises — just a plan that makes sense.'],
    ['Build', 'We design, develop and launch — whether that\'s a new website, an SEO campaign or your social presence.'],
    ['Grow', 'We measure, optimise and report, and we\'re here for ongoing support, hosting and improvements as you grow.'],
];

$benefits = [
    ['bi-geo-alt', 'Australian-Owned', 'A local team who understand the Australian market, with support in your timezone.'],
    ['bi-stack', 'All-in-One', 'Web, SEO, social and hosting managed together — one partner instead of five.'],
    ['bi-cash-coin', 'Transparent Pricing', 'Clear, upfront quotes and GST-inclusive tax invoices. You always know what you\'re paying.'],
    ['bi-people', 'Dedicated Support', 'A real person to talk to, not a ticket queue. We treat your business like our own.'],
    ['bi-speedometer2', 'Results-Focused', 'Everything we do is aimed at real outcomes — more traffic, more leads, more customers.'],
    ['bi-shield-check', 'Secure & Reliable', 'Best-practice security, backups and monitoring baked into everything we host and build.'],
];

$faqs = [
    ['How much does a website cost?', 'Every project is quoted individually based on what you need — from a clean starter website through to a fully custom, e-commerce build. Get in touch for a free, no-obligation quote and a clear fixed price.'],
    ['How long does SEO take to work?', 'SEO is a long-term investment rather than an overnight fix. Most businesses start to see meaningful movement within three to six months, with results compounding the longer you invest.'],
    ['Do you offer ongoing hosting and support?', 'Yes. We offer fully managed hosting with SSL, daily backups, uptime monitoring and priority support, plus ongoing website care so you\'re never left on your own after launch.'],
    ['Do you work with businesses outside your local area?', 'Absolutely. We work with businesses right across Australia and communicate remotely, so distance is never a barrier to working together.'],
    ['What\'s included in social media management?', 'Content planning, on-brand graphics and copy, scheduling and publishing, community management and regular performance reporting across the platforms that matter for your business.'],
    ['Are your prices GST-inclusive?', 'Yes. We\'re a registered Australian business, so all of our pricing and tax invoices are GST-inclusive and in AUD.'],
];

// --- Structured data (schema.org) -----------------------------------------
$addr = array_filter([
    'streetAddress' => $company['address']['line1'] ?? null,
    'addressLocality' => $company['address']['locality'] ?? null,
    'addressRegion' => $company['address']['region'] ?? null,
    'postalCode' => $company['address']['postcode'] ?? null,
    'addressCountry' => 'AU',
]);
$org = array_filter([
    '@type' => 'ProfessionalService',
    '@id' => $appUrl . '/#organization',
    'name' => $company['legal_name'],
    'url' => $appUrl,
    'email' => $company['email'],
    'telephone' => $company['phone'] ?: null,
    'logo' => $ogImage,
    'image' => $ogImage,
    'description' => $description,
    'priceRange' => '$$',
    'areaServed' => ['@type' => 'Country', 'name' => 'Australia'],
    'address' => $addr ? array_merge(['@type' => 'PostalAddress'], $addr) : null,
    'vatID' => $company['abn'] ?: null,
]);
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $org,
        ['@type' => 'WebSite', '@id' => $appUrl . '/#website', 'url' => $appUrl, 'name' => 'OptiTide', 'publisher' => ['@id' => $appUrl . '/#organization'], 'inLanguage' => 'en-AU'],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question', 'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ], $faqs)],
    ],
];

$isAuthed = \App\Core\Auth::check();
$dashUrl = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="<?= e($brand) ?>">
<link rel="canonical" href="<?= e($appUrl) ?>/">
<meta name="geo.region" content="AU">
<meta name="geo.placename" content="Australia">

<meta property="og:type" content="website">
<meta property="og:site_name" content="OptiTide">
<meta property="og:locale" content="en_AU">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($appUrl) ?>/">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<link rel="icon" href="/assets/img/optitide-mark.svg" type="image/svg+xml">
<link rel="icon" href="/assets/img/favicon.png" sizes="any">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">
<style>:root{--brand: <?= e($brand) ?>; --brand-dark: <?= e(config('app.brand.accent_dark', '#6d28d9')) ?>;}</style>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="mk">

<nav class="mk-nav">
    <div class="mk-container">
        <a href="/" aria-label="OptiTide home"><img class="brand-logo" src="/assets/img/logo.png" alt="OptiTide"></a>
        <div class="mk-nav-links">
            <a href="#services" class="mk-nav-link">Services</a>
            <a href="#process" class="mk-nav-link">Process</a>
            <a href="#why" class="mk-nav-link">Why Us</a>
            <a href="#faq" class="mk-nav-link">FAQ</a>
            <a href="#contact" class="mk-nav-link">Contact</a>
            <a href="<?= $dashUrl ?>" class="btn btn-brand btn-sm"><?= $isAuthed ? 'Dashboard' : 'Client Login' ?></a>
        </div>
    </div>
</nav>

<header class="mk-hero">
    <div class="mk-container">
        <?php $hasMascot = is_file(public_path('assets/img/mascot.png')); ?>
        <div class="row align-items-center g-4">
            <div class="col-lg-<?= $hasMascot ? '7' : '12' ?>">
        <span class="mk-badge"><i class="bi bi-stars"></i> Australian Digital Agency</span>
        <h1>Web Design, SEO &amp; Digital Marketing<br class="d-none d-md-inline"> for <span class="mk-gradient-text">Australian Business</span></h1>
        <p>OptiTide helps Australian businesses get found, look professional and grow online — with web design, SEO, social media and managed hosting, all under one roof.</p>
        <div class="mk-hero-cta">
            <a href="#contact" class="btn btn-brand btn-lg">Get a Free Quote</a>
            <a href="#services" class="btn btn-ghost btn-lg">Explore Services</a>
        </div>
        <div class="mk-hero-stats">
            <div><div class="n mk-gradient-text">All-in-One</div><div class="l">Web · SEO · Social · Hosting</div></div>
            <div><div class="n mk-gradient-text">Australian</div><div class="l">Owned &amp; operated</div></div>
            <div><div class="n mk-gradient-text">GST-Ready</div><div class="l">Clear, inclusive invoicing</div></div>
        </div>
            </div>
            <?php if ($hasMascot): ?>
                <div class="col-lg-5 text-center">
                    <img src="/assets/img/mascot.png" alt="OptiTide mascot" class="mk-hero-mascot" style="max-height:440px">
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<section id="services" class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">What We Do</span>
            <h2 class="mk-h2">Everything Your Business Needs Online</h2>
            <p class="mk-lead mx-auto">Four core services that work together — so your website, your search rankings, your social presence and your hosting all pull in the same direction.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $idx => [$icon, $sTitle, $blurb, $points]): ?>
                <?php $iconImg = 'assets/img/service-' . $serviceSlugs[$idx] . '.png'; ?>
                <div class="col-md-6">
                    <article class="mk-service">
                        <?php if (is_file(public_path($iconImg))): ?>
                            <img src="/<?= $iconImg ?>" alt="<?= e($sTitle) ?>" class="mk-service-icon-img brand-img">
                        <?php else: ?>
                            <div class="mk-service-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <?php endif; ?>
                        <h3><?= e($sTitle) ?></h3>
                        <p><?= e($blurb) ?></p>
                        <ul>
                            <?php foreach ($points as $point): ?>
                                <li><i class="bi bi-check-circle-fill"></i> <?= e($point) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="process" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">How We Work</span>
            <h2 class="mk-h2">A Simple, Transparent Process</h2>
            <p class="mk-lead mx-auto">No jargon and no surprises — just four clear steps from first conversation to ongoing growth.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($process as $i => [$stepTitle, $stepText]): ?>
                <div class="col-6 col-lg-3">
                    <div class="mk-step">
                        <div class="mk-step-num"><?= $i + 1 ?></div>
                        <h4><?= e($stepTitle) ?></h4>
                        <p><?= e($stepText) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="why" class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Why OptiTide</span>
            <h2 class="mk-h2">A Partner That Actually Cares</h2>
            <p class="mk-lead mx-auto">We're small enough to give you real attention and experienced enough to deliver — a genuine partner in your growth, not just another vendor.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($benefits as [$icon, $bTitle, $bText]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="mk-benefit">
                        <i class="bi <?= e($icon) ?>"></i>
                        <div>
                            <h4><?= e($bTitle) ?></h4>
                            <p><?= e($bText) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="faq" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">FAQ</span>
            <h2 class="mk-h2">Frequently Asked Questions</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $i => [$q, $a]): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                    <?= e($q) ?>
                                </button>
                            </h3>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted"><?= e($a) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="mk-section">
    <div class="mk-container">
        <div class="mk-ctaband mb-5">
            <h2>Ready to Grow Your Business Online?</h2>
            <p>Tell us a little about your business and what you're after. We'll get back to you with honest advice and a clear quote — no pressure.</p>
        </div>
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <span class="mk-eyebrow">Get in Touch</span>
                <h2 class="mk-h2">Let's Talk</h2>
                <p class="mk-lead">Prefer email? Reach us directly and we'll reply as soon as we can.</p>
                <ul class="mk-contact-info list-unstyled mt-3">
                    <li><i class="bi bi-envelope"></i> <a href="mailto:<?= e($company['email']) ?>" class="text-decoration-none"><?= e($company['email']) ?></a></li>
                    <?php if ($company['phone']): ?><li><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></li><?php endif; ?>
                    <li><i class="bi bi-geo-alt"></i> Serving businesses Australia-wide</li>
                    <?php if ($company['abn']): ?><li><i class="bi bi-building"></i> <?= e($company['legal_name']) ?> · ABN <?= e($company['abn']) ?></li><?php endif; ?>
                </ul>
            </div>
            <div class="col-lg-7">
                <div class="mk-contact-card">
                    <?php if (session('success')): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= e(session('success')) ?></div>
                    <?php endif; ?>
                    <?php if (errors()): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please check the form and try again.</div>
                    <?php endif; ?>
                    <form method="post" action="<?= route('contact.submit') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" value="<?= e(old('name')) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-Mail</label>
                                <input type="email" name="email" value="<?= e(old('email')) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-muted small">(optional)</span></label>
                                <input type="text" name="phone" value="<?= e(old('phone')) ?>" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">I'm Interested In</label>
                                <select name="service" class="form-select">
                                    <?php foreach (['Web Design', 'SEO', 'Social Media', 'Hosting', 'Not Sure Yet'] as $opt): ?>
                                        <option <?= old('service') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">How Can We Help?</label>
                                <textarea name="message" rows="4" class="form-control <?= has_error('message') ? 'is-invalid' : '' ?>" required><?= e(old('message')) ?></textarea>
                            </div>
                        </div>
                        <button class="btn btn-brand btn-lg mt-3">Send Enquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="mk-footer">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="/"><img class="brand-logo" src="/assets/img/logo-dark.png" alt="OptiTide" style="height:46px"></a>
                <p class="mt-2" style="color:#a7a3d6;font-size:.9rem;max-width:300px">Web design, SEO, social media and hosting for Australian business — all under one roof.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h5>Services</h5>
                <ul class="foot-links">
                    <li><a href="#services">Web Design</a></li>
                    <li><a href="#services">SEO</a></li>
                    <li><a href="#services">Social Media</a></li>
                    <li><a href="#services">Hosting</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h5>Company</h5>
                <ul class="foot-links">
                    <li><a href="#why">Why OptiTide</a></li>
                    <li><a href="#process">Our Process</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-2">
                <h5>Client Area</h5>
                <ul class="foot-links">
                    <li><a href="<?= $dashUrl ?>"><?= $isAuthed ? 'Dashboard' : 'Client Login' ?></a></li>
                    <li><a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a></li>
                </ul>
            </div>
        </div>
        <div class="mk-footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= e($company['legal_name']) ?><?= $company['abn'] ? ' · ABN ' . e($company['abn']) : '' ?></span>
            <span>Australian Digital Agency</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
