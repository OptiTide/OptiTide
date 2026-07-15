<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => 'OptiTide — Coming Soon']); ?></head>
<body>
<div class="landing">
    <header class="lp-nav">
        <div class="lp-container d-flex align-items-center justify-content-between">
            <a href="/" class="brand-mark lp-brand text-decoration-none"><img class="brand-icon" src="/assets/img/optitide-mark.svg" alt="">Opti<span>Tide</span></a>
            <a href="<?= route('login') ?>" class="btn btn-brand btn-sm">Client Login</a>
        </div>
    </header>

    <section class="lp-hero lp-container">
        <span class="lp-badge"><span class="lp-dot"></span> Coming Soon</span>
        <h1 class="lp-title">Digital Growth,<br>Done Properly.</h1>
        <p class="lp-sub">OptiTide builds and grows Australian brands — web design, SEO, social media and hosting, all under one roof, with honest GST-ready invoicing. Our new platform is nearly here.</p>
        <div class="lp-cta">
            <a href="<?= route('login') ?>" class="btn btn-brand btn-lg">Client Login</a>
            <a href="mailto:<?= e(config('company.email')) ?>" class="btn btn-outline-light btn-lg">Get in Touch</a>
        </div>
    </section>

    <section class="lp-container lp-services">
        <div class="row g-3">
            <?php
            $services = [
                ['bi-palette', 'Web Design', 'Fast, modern, conversion-focused websites that represent your brand and win customers.'],
                ['bi-graph-up-arrow', 'SEO', 'Climb the rankings with technical SEO, content and local search that brings the right traffic.'],
                ['bi-megaphone', 'Social Media', 'Consistent, on-brand social content and management that keeps your audience engaged.'],
                ['bi-hdd-network', 'Hosting', 'Reliable, secure managed hosting with monitoring and support — set and forget.'],
            ];
            foreach ($services as [$icon, $sTitle, $blurb]): ?>
                <div class="col-6 col-lg-3">
                    <div class="lp-card h-100">
                        <div class="lp-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <h3 class="lp-card-title"><?= e($sTitle) ?></h3>
                        <p class="lp-card-text"><?= e($blurb) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lp-container lp-features">
        <div class="row g-4">
            <?php
            $features = [
                ['bi-geo-alt', 'Australian-Owned', 'A local team and local support, with AUD and GST-ready tax invoices.'],
                ['bi-stack', 'All-in-One', 'Design, SEO, social and hosting — managed together, from one place.'],
                ['bi-shield-check', 'Transparent', 'Clear pricing and a client portal to track your services and invoices.'],
            ];
            foreach ($features as [$icon, $fTitle, $desc]): ?>
                <div class="col-md-4">
                    <div class="lp-feat">
                        <i class="bi <?= e($icon) ?>"></i>
                        <div>
                            <h4><?= e($fTitle) ?></h4>
                            <p><?= e($desc) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="lp-footer">
        <div class="lp-container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <a href="/" class="brand-mark lp-brand-sm text-decoration-none"><img class="brand-icon" src="/assets/img/optitide-mark.svg" alt="">Opti<span>Tide</span></a>
            <span class="lp-foot-meta">&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?><?= config('company.abn') ? ' &middot; ABN ' . e(config('company.abn')) : '' ?> &middot; <?= e(config('company.email')) ?></span>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
