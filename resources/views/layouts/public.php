<!doctype html>
<html lang="en-AU">
<head><?php $this->insert('partials.head', ['title' => $title ?? config('app.name', 'OptiTide')]); ?></head>
<body class="mk">

<?php $this->insert('partials.site-nav'); ?>

<section class="mk-section">
    <div class="mk-container">
        <?php $this->insert('partials.flash'); ?>
        <?= $this->yield('content') ?>
    </div>
</section>

<?php $this->insert('partials.site-footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->insert('partials.chat-widget'); ?>
</body>
</html>
