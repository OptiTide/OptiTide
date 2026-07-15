<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => 'OptiTide — Coming Soon']); ?></head>
<body>
<div class="coming-soon">
    <div>
        <div class="brand-mark cs-logo mb-3"><img class="brand-icon" src="/assets/img/optitide-mark.svg" alt="">Opti<span>Tide</span></div>
        <h1 class="cs-title">Coming Soon</h1>
        <p class="cs-tag">Web Design, SEO, Social Media &amp; Hosting — done properly. Something great is on the way.</p>
        <a href="<?= route('login') ?>" class="btn btn-brand btn-lg">Client Login</a>
        <p class="cs-foot">&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?> &middot; <?= e(config('company.email')) ?></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
