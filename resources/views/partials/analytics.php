<?php
/**
 * Tracking snippets for public pages. Renders ONLY from format-validated IDs and
 * emits fixed, official snippets — a stored value can never inject arbitrary
 * markup. Include inside <head> on public/marketing pages only.
 */
$ga4  = trim((string) config('analytics.ga4', ''));
$gtm  = trim((string) config('analytics.gtm', ''));
$gsc  = trim((string) config('analytics.gsc', ''));
$px   = trim((string) config('analytics.meta_pixel', ''));

$ga4  = preg_match('/^G-[A-Z0-9]{4,12}$/', $ga4) ? $ga4 : '';
$gtm  = preg_match('/^GTM-[A-Z0-9]{4,10}$/', $gtm) ? $gtm : '';
$gsc  = preg_match('/^[A-Za-z0-9_-]{20,100}$/', $gsc) ? $gsc : '';
$px   = preg_match('/^[0-9]{10,20}$/', $px) ? $px : '';
?>
<?php if ($gsc !== ''): ?>
<meta name="google-site-verification" content="<?= e($gsc) ?>">
<?php endif; ?>
<?php if ($gtm !== ''): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm) ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>
<?php if ($ga4 !== ''): ?>
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($ga4) ?>');</script>
<?php endif; ?>
<?php if ($px !== ''): ?>
<!-- Meta Pixel -->
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($px) ?>');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= e($px) ?>&ev=PageView&noscript=1" alt=""></noscript>
<?php endif; ?>
