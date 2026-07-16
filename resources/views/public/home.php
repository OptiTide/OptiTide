<?php
$company = config('company');
$appUrl = rtrim(config('app.url'), '/');
$brand = config('app.brand.accent', '#FF6A00');
$title = 'OptiTide — Web Design, SEO, Social Media & Hosting in Australia';
$description = 'OptiTide is an Australian digital agency delivering web design, SEO, social media marketing and managed hosting for small business — high-performance websites, higher Google rankings and more leads, with fixed pricing and no lock-in contracts.';
$ogImage = $appUrl . '/assets/img/favicon.png';

// ---------------------------------------------------------------------------
// Marketing content — edit these arrays to change the homepage copy.
// ---------------------------------------------------------------------------
$services = [
    ['bi-window-desktop', 'Web Design & Development', 'Beautiful, responsive websites built to convert visitors into customers.', ['Custom Website Design', 'E-commerce Solutions', 'Mobile-First & Fast Loading', 'Ongoing Support & Updates']],
    ['bi-search', 'Search Engine Optimisation', 'Rank higher on Google and get found by more of your ideal customers.', ['On-Page SEO', 'Technical SEO Audits', 'Keyword Research', 'Local SEO & Reporting']],
    ['bi-megaphone', 'Social Media Marketing', 'Grow your brand and engage your audience across social platforms.', ['Content Creation', 'Paid Social Advertising', 'Community Management', 'Performance Reporting']],
    ['bi-hdd-network', 'Managed Hosting', 'Secure, fast and reliable hosting with expert Australian support.', ['Australian Servers', 'Daily Backups', '24/7 Monitoring', 'Free SSL & Security']],
];

// Pricing packages. `mo` = monthly retainer, `once` = one-off project price.
// Adjust prices/features to your real packages; set `once` to '' to hide it.
$plans = [
    ['name' => 'Starter', 'blurb' => 'Perfect for small businesses getting online.', 'mo' => 149, 'once' => 1499, 'features' => ['5 Page Website', 'Mobile Responsive', 'Basic SEO Setup', 'Contact Form'], 'cta' => 'Get Started', 'popular' => false],
    ['name' => 'Growth', 'blurb' => 'Great for growing businesses seeking more leads.', 'mo' => 299, 'once' => 2999, 'features' => ['Up to 10 Pages', 'Technical SEO', 'Google Business Profile', 'Monthly Performance Report'], 'cta' => 'Get Started', 'popular' => false],
    ['name' => 'Pro', 'blurb' => 'Our best value for established businesses.', 'mo' => 599, 'once' => 5999, 'features' => ['Up to 20 Pages', 'Advanced SEO & Content', 'Social Media Management', 'Monthly Strategy Call'], 'cta' => 'Get Started', 'popular' => true],
    ['name' => 'Business', 'blurb' => 'Custom solutions for larger businesses.', 'mo' => 0, 'once' => 0, 'features' => ['Custom Website', 'Full SEO Campaign', 'Paid Advertising', 'Dedicated Account Manager'], 'cta' => 'Contact Us', 'popular' => false],
];

$process = [
    ['Discover', 'We learn about your business, goals and target audience.'],
    ['Plan', 'We create a tailored strategy and roadmap for success.'],
    ['Build', 'We design, develop and optimise your digital assets.'],
    ['Grow', 'We launch, promote and continuously improve results.'],
];

$benefits = [
    ['bi-award', 'Australian Experts', 'Local team, local support.'],
    ['bi-graph-up-arrow', 'Proven Results', 'Data-driven strategies that deliver.'],
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
        ['@type' => 'WebSite', '@id' => $appUrl . '/#website', 'url' => $appUrl, 'name' => 'OptiTide', 'publisher' => ['@id' => $appUrl . '/#organization'], 'inLanguage' => 'en-AU'],
        ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question', 'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ], $faqs)],
    ],
];

$isAuthed = \App\Core\Auth::check();
$dashUrl = $isAuthed ? (\App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard')) : route('login');
$startUrl = $isAuthed ? $dashUrl : route('register');
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
                        <h1>Web Design, SEO &amp; Digital Marketing That Drives Real Results for <span class="mk-orange">Australian Businesses</span>.</h1>
                        <p>We build high-performance websites, rank you on Google, and grow your brand online — so you get more leads, more customers, and more revenue.</p>
                        <ul class="mk-hero-checks">
                            <li><i class="bi bi-check-circle-fill"></i> More Traffic &amp; Leads</li>
                            <li><i class="bi bi-check-circle-fill"></i> Higher Rankings on Google</li>
                            <li><i class="bi bi-check-circle-fill"></i> Professional Websites That Convert</li>
                            <li><i class="bi bi-check-circle-fill"></i> Clear Pricing, No Lock-in Contracts</li>
                        </ul>
                        <div class="mk-hero-cta">
                            <a href="#proposal" class="btn btn-brand btn-lg">Get Your Free Proposal</a>
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
                            <div class="col-sm-6"><input type="text" name="name" class="form-control" placeholder="Your Name" required></div>
                            <div class="col-sm-6"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
                            <div class="col-sm-6"><input type="text" name="phone" class="form-control" placeholder="Phone Number"></div>
                            <div class="col-sm-6">
                                <select name="business_type" class="form-select">
                                    <option value="">Business Type</option>
                                    <?php foreach (['Trades & Services', 'Retail / E-commerce', 'Hospitality', 'Professional Services', 'Health & Fitness', 'Other'] as $bt): ?><option><?= e($bt) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <select name="service" class="form-select">
                                    <option value="">What do you need help with?</option>
                                    <?php foreach (['Web Design', 'SEO', 'Social Media', 'Managed Hosting', 'Everything / Not sure'] as $sv): ?><option><?= e($sv) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><textarea name="message" rows="3" class="form-control" placeholder="Tell us a bit about your business…"></textarea></div>
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
            <?php foreach ($services as [$icon, $sTitle, $blurb, $points]): ?>
                <div class="col-md-6 col-xl-3">
                    <article class="mk-service h-100">
                        <div class="mk-service-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <h3><?= e($sTitle) ?></h3>
                        <p><?= e($blurb) ?></p>
                        <ul>
                            <?php foreach ($points as $point): ?><li><i class="bi bi-check2"></i> <?= e($point) ?></li><?php endforeach; ?>
                        </ul>
                        <a href="#proposal" class="mk-service-more">Learn More <i class="bi bi-arrow-right"></i></a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= PRICING ================= -->
<section id="packages" class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-4">
            <h2 class="mk-h2">Simple, Transparent Pricing</h2>
            <p class="mk-lead mx-auto">Choose a package that fits your goals and budget.</p>
            <div class="mk-price-toggle" role="group" aria-label="Billing period">
                <button type="button" class="is-active" data-period="mo" onclick="otSetPeriod('mo', this)">Monthly</button>
                <button type="button" data-period="once" onclick="otSetPeriod('once', this)">One-off</button>
            </div>
            <?php if (\App\Support\Currency::isConverted()): ?>
                <p class="small text-muted mx-auto mt-2" style="max-width:640px"><i class="bi bi-info-circle"></i> Prices shown in <?= e(\App\Support\Currency::current()) ?> are indicative, converted from AUD. Invoices are issued in AUD.</p>
            <?php endif; ?>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($plans as $plan): ?>
                <?php $isContact = (int) $plan['mo'] === 0 && (int) $plan['once'] === 0; ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="mk-price-card <?= $plan['popular'] ? 'mk-price-card--popular' : '' ?> h-100">
                        <?php if ($plan['popular']): ?><div class="mk-price-badge">Most Popular</div><?php endif; ?>
                        <div class="mk-price-name"><?= e($plan['name']) ?></div>
                        <div class="mk-price-blurb"><?= e($plan['blurb']) ?></div>
                        <div class="mk-price-amount">
                            <?php if ($isContact): ?>
                                <span class="mk-price-num">Let's talk</span>
                            <?php else: ?>
                                <span class="mk-price-num"
                                      data-mo="<?= e(\App\Support\Currency::display((int) $plan['mo'] * 100)) ?>"
                                      data-once="<?= $plan['once'] ? e(\App\Support\Currency::display((int) $plan['once'] * 100)) : 'Custom' ?>"><?= e(\App\Support\Currency::display((int) $plan['mo'] * 100)) ?></span><span class="mk-price-per" data-mo="/mo" data-once=" once">/mo</span>
                            <?php endif; ?>
                        </div>
                        <ul class="mk-price-features">
                            <?php foreach ($plan['features'] as $f): ?><li><i class="bi bi-check-circle-fill"></i> <?= e($f) ?></li><?php endforeach; ?>
                        </ul>
                        <a href="<?= $isContact ? '#contact' : $startUrl ?>" class="btn <?= $plan['popular'] ? 'btn-brand' : 'btn-outline-brand' ?> w-100 mt-auto"><?= e($plan['cta']) ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted small mt-4 mb-0">All plans include Australian hosting, SSL certificate &amp; ongoing support.</p>
    </div>
</section>

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
        <div class="text-center mb-5"><h2 class="mk-h2">Why Choose OptiTide?</h2></div>
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
                    <li><i class="bi bi-clock"></i> Mon – Fri, 9am – 5pm AEST</li>
                </ul>
                <div class="mk-chat-card mt-4">
                    <?php if ($hasMascot): ?><img src="/assets/img/mascot.png" alt="" class="mk-chat-mascot"><?php endif; ?>
                    <div>
                        <div class="fw-bold">Prefer to chat?</div>
                        <?php if ($company['phone']): ?><div class="small">Call us on <strong><?= e($company['phone']) ?></strong></div><?php endif; ?>
                        <div class="small text-muted">Mon – Fri, 9am – 5pm AEST</div>
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
                            <div class="col-md-6"><label class="form-label" for="c_name">Name</label><input id="c_name" type="text" name="name" value="<?= e(old('name')) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" placeholder="Your Name" required></div>
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
<script>
function otSetPeriod(period, btn) {
    document.querySelectorAll('.mk-price-toggle button').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
    document.querySelectorAll('.mk-price-num[data-' + period + ']').forEach(function (el) { el.textContent = el.getAttribute('data-' + period); });
    document.querySelectorAll('.mk-price-per[data-' + period + ']').forEach(function (el) { el.textContent = el.getAttribute('data-' + period); });
}
</script>
<?php $this->insert('partials.chat-widget'); ?>
</body>
</html>
