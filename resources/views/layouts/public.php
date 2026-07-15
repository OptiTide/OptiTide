<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => $title ?? config('app.name', 'OptiTide')]); ?></head>
<body>
<nav class="public-nav">
    <div class="container d-flex align-items-center justify-content-between py-3">
        <a href="/"><img class="brand-logo" src="/assets/img/logo.png" alt="OptiTide"></a>
        <div class="d-flex gap-2">
            <?php if (\App\Core\Auth::check()): ?>
                <a class="btn btn-sm btn-brand" href="<?= \App\Core\Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard') ?>">Go to Dashboard</a>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-brand" href="<?= route('login') ?>">Client Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php $this->insert('partials.flash'); ?>
    <?= $this->yield('content') ?>
</div>

<footer class="border-top mt-5">
    <div class="container py-4 d-flex flex-wrap justify-content-between text-muted small">
        <span>&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?><?= config('company.abn') ? ' · ABN ' . e(config('company.abn')) : '' ?></span>
        <span><?= e(config('company.email')) ?></span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
