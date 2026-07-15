<?php
$company = config('company');
$appUrl = rtrim(config('app.url'), '/');
$brand = config('app.brand.accent', '#7c3aed');
$title = 'OptiTide — Web Design, SEO, Social Media & Hosting in Australia';
$description = 'OptiTide is an Australian digital agency delivering web design, SEO, social media marketing and managed hosting for small business — all under one roof, with fixed upfront pricing and no surprise bills.';
$ogImage = $appUrl . '/assets/img/favicon.png';

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
    ['bi-cash-coin', 'No Surprise Bills', 'You get a clear, fixed quote before we start — the price we quote is the price you pay. Every invoice is a proper Australian tax invoice with GST already included.'],
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
    ['Is GST included in the price?', 'Yes. We\'re a registered Australian business, so every price you see already includes GST (10%) — there\'s nothing added on top. You\'ll always get a proper tax invoice in Australian dollars.'],
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
$canOrder = $isAuthed && \App\Core\Auth::isClient();
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

<link rel="icon" href="/assets/img/favicon.png" sizes="any">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">
<style>:root{--brand: <?= e($brand) ?>; --brand-dark: <?= e(config('app.brand.accent_dark', '#6d28d9')) ?>;}</style>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php $this->insert('partials.analytics'); ?>
<?php $this->insert('partials.pwa'); ?>
</head>
<body class="mk">

<?php $this->insert('partials.site-nav'); ?>

<header class="mk-hero">
    <div class="mk-container">
        <?php $hasMascot = is_file(public_path('assets/img/mascot.png')); ?>
        <div class="row align-items-center g-4">
            <div class="col-lg-<?= $hasMascot ? '7' : '12' ?>">
        <span class="mk-badge"><i class="bi bi-stars"></i> Australian Digital Agency</span>
        <h1>Web Design, SEO &amp; Digital Marketing<br class="d-none d-md-inline"> for <span class="mk-gradient-text">Australian Business</span></h1>
        <p>We build websites that win you customers, get your business found on Google, and keep you looking professional online — web design, SEO, social media and hosting, all handled by one Australian team. No jargon, no surprise bills.</p>
        <div class="mk-hero-cta">
            <a href="#contact" class="btn btn-brand btn-lg">Get a Free Quote</a>
            <a href="<?= ! empty($packages) ? '#packages' : '#contact' ?>" class="btn btn-ghost btn-lg">See Pricing</a>
        </div>
        <div class="mk-hero-stats">
            <div><div class="n mk-gradient-text">One Team</div><div class="l">Web, SEO, social &amp; hosting in one place</div></div>
            <div><div class="n mk-gradient-text">Australian</div><div class="l">Local team, real people, real support</div></div>
            <div><div class="n mk-gradient-text">Fixed Pricing</div><div class="l">Clear quotes upfront — no surprise bills</div></div>
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

<!-- Trust strip (honest signals, no fabricated reviews) -->
<div class="mk-trustbar">
    <div class="mk-container">
        <span class="mk-trust-item"><i class="bi bi-geo-alt-fill"></i> Australian owned &amp; operated</span>
        <span class="mk-trust-item"><i class="bi bi-receipt"></i> GST-registered · ABN <?= e(config('company.abn')) ?></span>
        <span class="mk-trust-item"><i class="bi bi-cash-coin"></i> Fixed pricing — no surprise bills</span>
        <span class="mk-trust-item"><i class="bi bi-headset"></i> Real support from a local team</span>
        <span class="mk-trust-item"><i class="bi bi-shield-check"></i> Free, no-obligation quotes</span>
    </div>
</div>

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

<?php if (! empty($packages)): ?>
<section id="packages" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">Plans &amp; Packages</span>
            <h2 class="mk-h2">Simple, Transparent Pricing</h2>
            <p class="mk-lead mx-auto">Simple packages for every service — pick a plan that fits, or ask us for a custom quote. All prices already include GST, so the price you see is the price you pay.</p>
            <?php if (\App\Support\Currency::isConverted()): ?>
                <p class="small text-muted mx-auto" style="max-width:640px"><i class="bi bi-info-circle"></i> Prices shown in <?= e(\App\Support\Currency::current()) ?> are indicative, converted from Australian dollars. Invoices are issued and settled in AUD.</p>
            <?php endif; ?>
        </div>
        <?php foreach ($packages as $group): ?>
            <div class="mb-4">
                <h3 class="h5 fw-bold mb-3"><?= e($group['line']['name']) ?></h3>
                <div class="row g-3">
                    <?php foreach ($group['plans'] as $plan): ?>
                        <?php $isCustom = stripos($plan['name'], 'custom') !== false; ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="mk-plan <?= $isCustom ? 'mk-plan--custom' : '' ?>">
                                <div class="mk-plan-name"><?= e($plan['name']) ?></div>
                                <div class="mk-plan-price">
                                    <?php if ((int) $plan['price_cents'] === 0): ?>
                                        <span style="font-size:1.2rem">Custom Quote</span>
                                    <?php else: ?>
                                        <?php if ($isCustom): ?><span class="mk-plan-from">from</span> <?php endif; ?><?= e(\App\Support\Currency::display((int) $plan['price_cents'])) ?><?php if ($plan['billing_type'] === 'recurring'): ?><span class="mk-plan-per">/<?= e(substr($plan['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isCustom): ?>
                                    <a href="#contact" class="btn btn-sm btn-outline-brand w-100 mt-3">Get a Quote</a>
                                <?php elseif ($canOrder): ?>
                                    <a href="<?= route('portal.order.show', ['service' => $plan['id']]) ?>" class="btn btn-sm btn-brand w-100 mt-3">Order Now</a>
                                <?php else: ?>
                                    <a href="<?= route('register') ?>" class="btn btn-sm btn-brand w-100 mt-3">Get Started</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section id="process" class="mk-section">
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
                            <div class="col-12">
                                <label class="form-label">Quick check: <?= e($captcha ?? 'What is 3 + 4?') ?> <span class="text-danger">*</span></label>
                                <input type="text" name="captcha" inputmode="numeric" autocomplete="off" class="form-control <?= has_error('captcha') ? 'is-invalid' : '' ?>" style="max-width:180px" required>
                                <?php if (error('captcha')): ?><div class="invalid-feedback"><?= e(error('captcha')) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-brand btn-lg mt-3">Send Enquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->insert('partials.site-footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->insert('partials.chat-widget'); ?>
</body>
</html>
