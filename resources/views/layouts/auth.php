<!doctype html>
<html lang="en-AU">
<head><?php $this->insert('partials.head', ['title' => $title ?? 'Sign in — OptiTide']); ?></head>
<body class="mk">

<?php $this->insert('partials.site-nav'); ?>

<div class="auth-body">
    <div class="auth-card">
        <?php $this->insert('partials.flash'); ?>
        <?= $this->yield('content') ?>
    </div>
</div>

<?php $this->insert('partials.site-footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
