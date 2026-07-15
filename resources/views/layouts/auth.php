<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => $title ?? 'Sign in — OptiTide']); ?></head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <a href="/" class="text-decoration-none brand-mark fs-3"><img class="brand-icon" src="/assets/img/optitide-mark.svg" alt="">Opti<span>Tide</span></a>
        </div>
        <?php $this->insert('partials.flash'); ?>
        <?= $this->yield('content') ?>
        <p class="text-center text-muted small mt-4 mb-0">&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
