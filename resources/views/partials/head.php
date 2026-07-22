<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php
// SEO comes from the shared partial so no page can ship without it. This file is
// used by the app shells (admin, portal, auth) AND by layouts/public.php, which is
// why noindex can no longer be hardcoded here: it made /terms, /privacy and
// /refund noindex while sitemap.xml listed them. Each layout now says which it is.
$this->insert('partials.seo', [
    'title'          => $title ?? null,
    'seoTitle'       => $seoTitle ?? null,
    'seoDescription' => $seoDescription ?? null,
    'canonical'      => $canonical ?? null,
    'ogImage'        => $ogImage ?? null,
    'ogType'         => $ogType ?? null,
    'jsonLd'         => $jsonLd ?? null,
    // Default to noindex: this partial's main callers are the signed-in app, and
    // failing closed keeps a client's dashboard out of Google. Public layouts
    // pass false explicitly.
    'noindex'        => $noindex ?? true,
]);
?>
<link rel="icon" href="/assets/img/favicon.png" sizes="any">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>:root{--brand: <?= e(config('app.brand.accent', '#FF6A00')) ?>; --brand-dark: <?= e(config('app.brand.accent_dark', '#E85F00')) ?>;}</style>
<?php $this->insert('partials.pwa'); ?>
