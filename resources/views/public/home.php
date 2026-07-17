<?php
$company = config('company');
$appUrl = rtrim(config('app.url'), '/');
$brand = config('app.brand.accent', '#FF6A00');
$title = config('company.brand_name') . ' — Web Design, SEO, Social Media & Hosting in Australia';
$description = config('company.brand_name') . ' is an Australian digital agency delivering web design, SEO, social media marketing and web hosting for small business — high-performance websites, higher Google rankings and more leads, with fixed pricing and no lock-in contracts.';
$ogImage = $appUrl . '/assets/img/favicon.png';

// ---------------------------------------------------------------------------
// Marketing content — edit these arrays to change the homepage copy.
// ---------------------------------------------------------------------------
// The four service lines come from PageController::serviceData() — the SAME map
// the nav, the hero chips and the service pages use. This section used to keep
// its own hardcoded copy, which is how "Managed Hosting" and "E-commerce" drifted
// back in after being fixed on the service pages.
$services = \App\Controllers\PublicSite\PageController::serviceData();

$process = [
    ['Discover', 'We learn about your business, goals and target audience.'],
    ['Plan', 'We create a tailored strategy and roadmap for success.'],
    ['Build', 'We design, develop and optimise your digital assets.'],
    ['Grow', 'We launch, promote and continuously improve results.'],
];

$benefits = [
    ['bi-award', 'Australian Experts', 'Local team, local support.'],
    ['bi-graph-up-arrow', 'Clear Reporting', 'Transparent monthly reporting on what we did and what moved.'],
    ['bi-unlock', 'No Lock-in Contracts', 'Stay because you want to, not because you have to.'],
    ['bi-tag', 'Transparent Pricing', 'No hidden fees, just honest pricing.'],
    ['bi-headset', 'Ongoing Support', 'We\'re here when you need us most.'],
    ['bi-heart', 'Passionate Team', 'We care about your success.'],
];

$faqs = [
    ['How long does it take to build a website?', 'Most websites are completed within 2–4 weeks depending on the size and complexity. We\'ll provide a clear timeline during our discovery call.'],
    ['Will my website be mobile friendly?', 'Absolutely — every website we build is fully responsive and optimised for mobile, tablet and desktop out of the box.'],
    ['Do you offer ongoing SEO services?', 'Yes. We offer monthly SEO retainers that grow your organic traffic and rankings over time, with transparent reporting.'],
    ['Do you lock clients into long-term contracts?', 'No lock-in contracts. We earn your business every month with real results and genuine service.'],
    ['Where are you based?', 'We\'re an Australian-owned business serving clients right across the country, with local support in your timezone.'],
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
        ['@type' => 'WebSite', '@id' => $appUrl . '/#website', 'url' => $appUrl, 'name' => config('company.brand_name'), 'publisher' => ['@id' => $appUrl . '/#organization'], 'inLanguage' => 'en-AU'],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question', 'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ], $faqs)],
    ],
];

$isAuthed = \App\Core\Auth::check();
$dashUrl = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
$startUrl = $isAuthed ? route('portal.order.index') : route('register');
$canOrder = $isAuthed && \App\Core\Auth::isClient();
$hasMascot = is_file(public_path('assets/img/mascot.png'));
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#0D1530">
<link rel="canonical" href="<?= e($appUrl) ?>/">
<?php // The homepage builds its own head instead of using layouts.marketing, so
      // the feed link is repeated here or autodiscovery misses the front door. ?>
<?php if (\App\Support\Features::enabled('blog')): ?>
<link rel="alternate" type="application/rss+xml" title="<?= e(config('company.brand_name')) ?> Blog" href="<?= route('blog.rss') ?>">
<?php endif; ?>
<meta name="geo.region" content="AU">
<meta name="geo.placename" content="Australia">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(config('company.brand_name')) ?>">
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
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>:root{--brand: <?= e($brand) ?>; --brand-dark: <?= e(config('app.brand.accent_dark', '#E85F00')) ?>;}</style>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php $this->insert('partials.analytics'); ?>
<?php $this->insert('partials.pwa'); ?>
</head>
<body class="mk">

<?php $this->insert('partials.site-nav'); ?>

<!-- ================= HERO ================= -->
<header class="mk-hero" id="proposal">
    <div class="mk-container">
        <div class="row g-4 g-xl-5 align-items-center">
            <div class="col-lg-7">
                <div class="mk-hero-inner">
                    <div class="mk-hero-copy">
                        <h1>Web Design, SEO, Social Media &amp; Hosting That Drive Real Results for <span class="mk-orange">Australian Businesses</span>.</h1>
                        <p>We build high-performance websites, rank you on Google, grow your brand online and keep it all running — so you get more leads, more customers, and more revenue.</p>
                        <?php // All four lines, from the same map the nav and pages use — so
                              // the hero can never advertise a service we don't list. ?>
                        <div class="mk-hero-services">
                            <?php foreach (\App\Controllers\PublicSite\PageController::serviceData() as $svcSlug => $svc): ?>
                                <a class="mk-hero-service" href="/services/<?= e($svcSlug) ?>">
                                    <i class="bi <?= e($svc['icon']) ?>"></i> <?= e($svc['nav']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <ul class="mk-hero-checks">
                            <li><i class="bi bi-check-circle-fill"></i> More Traffic &amp; Leads</li>
                            <li><i class="bi bi-check-circle-fill"></i> Higher Rankings on Google</li>
                            <li><i class="bi bi-check-circle-fill"></i> Professional Websites That Convert</li>
                            <li><i class="bi bi-check-circle-fill"></i> Clear Pricing, No Lock-in Contracts</li>
                        </ul>
                        <div class="mk-hero-cta">
                            <a href="#get-proposal" class="btn btn-brand btn-lg">Get Your Free Proposal</a>
                            <a href="#packages" class="btn btn-ghost btn-lg">View Our Packages</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="mk-hero-form" id="get-proposal">
                    <h2>Get Your Free Proposal</h2>
                    <p class="mk-hero-form-sub">Tell us about your business and we'll send a custom proposal within 24 hours.</p>
                    <?php if (session('success')): ?>
                        <div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> <?= e(session('success')) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= route('proposal.submit') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-2">
                            <div class="col-sm-6"><input type="text" name="name" class="form-control" autocomplete="name" placeholder="Your Full Name" required></div>
                            <div class="col-sm-6"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
                            <div class="col-sm-6"><input type="text" name="phone" class="form-control" placeholder="Phone Number"></div>
                            <div class="col-sm-6">
                                <select name="business_type" class="form-select">
                                    <option value="">Business Type</option>
                                    <?php foreach (['Trades & Services', 'Retail / E-Commerce', 'Hospitality', 'Professional Services', 'Health & Fitness', 'Other'] as $bt): ?><option><?= e($bt) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <select name="service" class="form-select">
                                    <option value="">What do you need help with?</option>
                                    <?php // Driven off the real service map, so this can never offer
                                          // a line we don't sell (it used to say "Managed Hosting"). ?>
                                    <?php foreach (\App\Controllers\PublicSite\PageController::serviceData() as $sv): ?><option><?= e($sv['nav']) ?></option><?php endforeach; ?>
                                    <option>Everything / Not sure</option>
                                </select>
                            </div>
                            <div class="col-12"><textarea name="message" rows="3" class="form-control" placeholder="Tell us a bit about your business…"></textarea></div>
                            <div class="col-12">
                                <?php // Same session challenge as the contact form below — the
                                      // controller calls Captcha::question() once, so both forms
                                      // share one answer and neither invalidates the other. ?>
                                <?php $this->insert('partials.captcha-field', ['captcha' => $captcha ?? null, 'captchaId' => 'p_captcha', 'captchaCompact' => true]); ?>
                            </div>
                        </div>
                        <button class="btn btn-brand w-100 mt-3">Send My Free Proposal <i class="bi bi-arrow-right"></i></button>
                        <div class="mk-hero-form-note"><i class="bi bi-check-circle-fill"></i> 100% FREE · No Obligation · Fast Response</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ================= TRUST BAR ================= -->
<div class="mk-trustbar">
    <div class="mk-container">
        <span class="mk-trust-item"><i class="bi bi-geo-alt-fill"></i> Australian Owned &amp; Operated</span>
        <span class="mk-trust-item"><i class="bi bi-shield-lock"></i> No Lock-in Contracts</span>
        <span class="mk-trust-item"><i class="bi bi-cash-coin"></i> Transparent Pricing</span>
        <span class="mk-trust-item"><i class="bi bi-clock-history"></i> Fast Turnaround Times</span>
        <span class="mk-trust-item"><i class="bi bi-graph-up-arrow"></i> Results Focused</span>
    </div>
</div>

<!-- ================= SERVICES ================= -->
<section id="services" class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5">
            <h2 class="mk-h2">Our Core Services</h2>
            <p class="mk-lead mx-auto">Everything you need to grow your business online.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $sSlug => $s): ?>
                <?php $sFrom = \App\Support\Catalog::fromPriceCents($s['category']); ?>
                <div class="col-md-6 col-xl-3">
                    <article class="mk-service h-100">
                        <div class="mk-service-icon"><i class="bi <?= e($s['icon']) ?>"></i></div>
                        <h3><?= e($s['title']) ?></h3>
                        <?php if ($sFrom): ?>
                            <div class="mk-service-from">From <strong><?= e(\App\Support\Currency::display($sFrom)) ?></strong></div>
                        <?php endif; ?>
                        <p><?= e($s['blurb']) ?></p>
                        <ul>
                            <?php foreach (array_slice($s['includes'], 0, 4) as [$pIcon, $pTitle, $pDesc]): ?>
                                <li><i class="bi bi-check2"></i> <?= e($pTitle) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php // Goes to the actual service page — this used to point at
                              // #proposal, so "Learn More" never taught you anything. ?>
                        <a href="/services/<?= e($sSlug) ?>" class="mk-service-more">Learn More About <?= e($s['nav']) ?> <i class="bi bi-arrow-right"></i></a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= PRICING (real catalogue, managed in /admin) ============ -->
<?php if (! empty($packages)): ?>
<section id="packages" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <h2 class="mk-h2">Simple, Transparent Pricing</h2>
            <p class="mk-lead mx-auto">Real prices, GST included, no lock-in contracts — the price you see is the price you pay.</p>
            <?php if (\App\Support\Currency::isConverted()): ?>
                <p class="small text-muted mx-auto mt-2" style="max-width:640px"><i class="bi bi-info-circle"></i> Prices shown in <?= e(\App\Support\Currency::current()) ?> are indicative, converted from AUD. Invoices are issued in AUD.</p>
            <?php endif; ?>
        </div>

        <?php $this->insert('public.pages.partials.pricing-grid', [
            'packages' => $packages, 'canOrder' => $canOrder, 'startUrl' => $startUrl,
        ]); ?>
        <p class="text-center text-muted small mt-4 mb-0">All prices in <?= e(config('company.currency', 'AUD')) ?> and include GST. Every plan comes with Australian support.</p>
    </div>
</section>
<?php endif; ?>

<!-- ================= PROCESS ================= -->
<section id="process" class="mk-process-section">
    <div class="mk-container">
        <div class="text-center mb-5"><h2 class="mk-h2">Our Simple 4-Step Process</h2></div>
        <div class="row g-4 mk-process-row">
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

<!-- ================= WHY CHOOSE ================= -->
<section id="about" class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5"><h2 class="mk-h2">Why Choose <?= e(config('company.brand_name')) ?>?</h2></div>
        <div class="row g-4">
            <?php foreach ($benefits as [$icon, $bTitle, $bText]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="mk-benefit">
                        <i class="bi <?= e($icon) ?>"></i>
                        <div><h4><?= e($bTitle) ?></h4><p><?= e($bText) ?></p></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= FAQ ================= -->
<section id="faq" class="mk-section">
    <div class="mk-container">
        <div class="text-center mb-5"><h2 class="mk-h2">Frequently Asked Questions</h2></div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $i => [$q, $a]): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq<?= $i ?>"><?= e($q) ?></button>
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

<!-- ================= CTA BAND ================= -->
<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Ready to Grow Your Business Online?</h2>
            <p>Get your free proposal today and discover how we can help you attract more customers and grow revenue.</p>
        </div>
        <a href="#proposal" class="btn btn-brand btn-lg">Get My Free Proposal <i class="bi bi-arrow-right"></i></a>
    </div>
</section>

<!-- ================= CONTACT ================= -->
<section id="contact" class="mk-section">
    <div class="mk-container">
        <div class="row g-4">
            <div class="col-lg-5">
                <span class="mk-eyebrow">Let's Talk</span>
                <h2 class="mk-h2">Let's Talk About Your Project</h2>
                <p class="mk-lead">We'd love to help your business succeed online.</p>
                <ul class="mk-contact-info list-unstyled mt-3">
                    <?php if ($company['phone']): ?><li><i class="bi bi-telephone"></i> <?= e($company['phone']) ?></li><?php endif; ?>
                    <li><i class="bi bi-envelope"></i> <a href="mailto:<?= e($company['email']) ?>" class="text-decoration-none"><?= e($company['email']) ?></a></li>
                    <li><i class="bi bi-geo-alt"></i> Serving businesses Australia-wide</li>
                    <li><i class="bi bi-clock"></i> <?= e(config('company.hours')) ?></li>
                </ul>
                <div class="mk-chat-card mt-4">
                    <?php if ($hasMascot): ?><img src="/assets/img/mascot.png" alt="" class="mk-chat-mascot"><?php endif; ?>
                    <div>
                        <div class="fw-bold">Prefer to chat?</div>
                        <?php if ($company['phone']): ?><div class="small">Call us on <strong><?= e($company['phone']) ?></strong></div><?php endif; ?>
                        <div class="small text-muted"><?= e(config('company.hours')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="mk-contact-card">
                    <?php if (errors()): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please check the form and try again.</div><?php endif; ?>
                    <form method="post" action="<?= route('contact.submit') ?>" novalidate>
                        <?= csrf_field() ?>
                        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="c_name">Full Name</label><input id="c_name" type="text" name="name" value="<?= e(old('name')) ?>" autocomplete="name" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" placeholder="Your full name" required></div>
                            <div class="col-md-6"><label class="form-label" for="c_email">Email Address</label><input id="c_email" type="email" name="email" value="<?= e(old('email')) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" placeholder="Email Address" required></div>
                            <div class="col-md-6"><label class="form-label" for="c_phone">Phone Number</label><input id="c_phone" type="text" name="phone" value="<?= e(old('phone')) ?>" class="form-control" placeholder="Phone Number"></div>
                            <div class="col-md-6"><label class="form-label" for="c_service">How can we help?</label>
                                <select id="c_service" name="service" class="form-select">
                                    <?php foreach (['Web Design', 'SEO', 'Social Media', 'Hosting', 'Not sure yet'] as $opt): ?><option <?= old('service') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label" for="c_msg">Tell us more about your project…</label><textarea id="c_msg" name="message" rows="4" class="form-control <?= has_error('message') ? 'is-invalid' : '' ?>" placeholder="Tell us more about your project…" required><?= e(old('message')) ?></textarea></div>
                            <div class="col-12">
                                <label class="form-label" for="c_captcha">Quick check: <?= e($captcha ?? 'What is 3 + 4?') ?> <span class="text-danger">*</span></label>
                                <input id="c_captcha" type="text" name="captcha" inputmode="numeric" autocomplete="off" class="form-control <?= has_error('captcha') ? 'is-invalid' : '' ?>" style="max-width:180px" required>
                                <?php if (error('captcha')): ?><div class="invalid-feedback d-block"><?= e(error('captcha')) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn-brand btn-lg mt-3">Send Message</button>
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
