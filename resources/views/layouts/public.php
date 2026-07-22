<!doctype html>
<html lang="en-AU">
<?php // PUBLIC pages (terms, privacy, refund). These were rendered noindex by
      // partials/head while sitemap.xml listed them — Search Console reports that
      // combination as an error. They are legitimate pages and a visible legal
      // policy is a trust signal, so they index. ?>
<head><?php $this->insert('partials.head', [
    'title'          => $title ?? config('company.brand_name'),
    'seoTitle'       => $seoTitle ?? null,
    'seoDescription' => $seoDescription ?? null,
    'canonical'      => $canonical ?? null,
    'jsonLd'         => $jsonLd ?? null,
    'noindex'        => false,
]); ?></head>
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
