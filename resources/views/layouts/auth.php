<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => $title ?? 'Sign in — OptiTide']); ?></head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <a href="/" class="d-inline-block bg-white rounded-3 shadow-sm px-3 py-2"><img src="/assets/img/logo.png" alt="OptiTide" class="brand-logo" style="height:40px"></a>
        </div>
        <?php $this->insert('partials.flash'); ?>
        <?= $this->yield('content') ?>
        <p class="text-center text-muted small mt-4 mb-0">&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
