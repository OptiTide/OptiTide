<?php
/**
 * The single source of SEO markup for EVERY page on the site.
 *
 * Before this, SEO lived only in layouts/marketing.php. layouts/public.php and
 * layouts/auth.php used partials/head.php, which hardcoded noindex — so /terms,
 * /privacy and /refund were served noindex WHILE being listed in sitemap.xml
 * (Search Console reports that as an error), with no description, canonical, OG
 * or Twitter tags at all.
 *
 * Patching those pages one by one would leave the next page someone adds in the
 * same state. So every layout now includes this, and a page is indexable-with-
 * full-tags by default; a layout opts OUT by setting $noindex (admin, portal and
 * auth do).
 *
 * Vars, all optional:
 *   $seoTitle       <title> + og:title      (falls back to $title, then brand)
 *   $seoDescription meta description        (falls back to the company blurb)
 *   $canonical      absolute canonical URL  (falls back to the current URL)
 *   $ogImage        absolute image URL      (falls back to the branded card)
 *   $ogType         og:type                 (default 'website')
 *   $jsonLd         array -> JSON-LD block
 *   $noindex        true to keep a page out of the index
 */
$appUrl = rtrim(config('app.url'), '/');
$company = config('company');

$seoTitle = $seoTitle ?? (isset($title) ? $title . ' — ' . $company['brand_name'] : $company['brand_name']);
$seoDescription = $seoDescription ?? ($company['tagline'] ?? 'Web design, SEO, social media and hosting for Australian business.');

// Canonical defaults to the current path — never the query string, so paginated
// and filtered variants don't fragment into separate indexed URLs.
if (empty($canonical)) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $canonical = $appUrl . ($path === '/' ? '/' : rtrim($path, '/'));
}

// og-default.png is the 1200x630 branded card built for this; the favicon is a
// 512px square that social platforms render as a smear.
$ogImage = $ogImage ?? ($appUrl . '/assets/img/og-default.png');
$ogType = $ogType ?? 'website';
$noindex = $noindex ?? false;
?>
<title><?= e($seoTitle) ?></title>
<meta name="description" content="<?= e($seoDescription) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' ?>">
<meta name="theme-color" content="<?= e(config('app.brand.accent', '#FF6A00')) ?>">
<meta name="geo.region" content="AU">
<meta name="geo.placename" content="<?= e($company['address']['locality'] ?? 'Australia') ?>">

<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="<?= e($company['brand_name']) ?>">
<meta property="og:locale" content="en_AU">
<meta property="og:title" content="<?= e($seoTitle) ?>">
<meta property="og:description" content="<?= e($seoDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seoTitle) ?>">
<meta name="twitter:description" content="<?= e($seoDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if (\App\Support\Features::enabled('blog')): ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($company['brand_name']) ?> Blog" href="<?= route('blog.rss') ?>">
<?php endif; ?>
<?php if (! empty($jsonLd)): ?>
<?php // JSON_HEX_TAG is load-bearing: a "</script>" inside any value would
      // otherwise break out of the script element. ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
