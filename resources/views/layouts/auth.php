<!doctype html>
<html lang="en-AU">
<head><?php $this->insert('partials.head', ['title' => $title ?? 'Sign in — OptiTide']); ?></head>
<body class="mk">

<nav class="mk-nav">
    <div class="mk-container">
        <a href="/" aria-label="OptiTide home"><img class="brand-logo" src="/assets/img/logo.png" alt="OptiTide"></a>
        <button class="mk-nav-toggle" type="button" aria-label="Menu" aria-expanded="false" onclick="var m=document.getElementById('mkNav');m.classList.toggle('open');this.setAttribute('aria-expanded',m.classList.contains('open'))"><i class="bi bi-list"></i></button>
        <div class="mk-nav-links" id="mkNav">
            <a href="/" class="mk-nav-link">Home</a>
            <a href="/#services" class="mk-nav-link">Services</a>
            <a href="/#packages" class="mk-nav-link">Packages</a>
            <a href="<?= route('blog.index') ?>" class="mk-nav-link">Blog</a>
            <a href="/#contact" class="mk-nav-link">Contact</a>
        </div>
    </div>
</nav>

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
