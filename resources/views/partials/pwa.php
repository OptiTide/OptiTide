<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0D1530">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(config('company.brand_name')) ?>">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<?php /*
   NO service worker is registered any more — this only cleans up.

   Logging in landed on the raw /sw.js source three times. It was patched twice
   (once in the worker's fetch handler, once by forcing stuck clients to update)
   and it kept happening. The worker is provably correct and the server cannot
   emit /sw.js as a redirect, so the fault is browser-held state I can't see. I
   couldn't reproduce it, so I couldn't honestly claim a third patch had fixed it.

   With nothing registered there is nothing to intercept a navigation and nothing
   to go stale. The offline shell this bought (one stylesheet, two images) was not
   worth the front door of the business.

   Two cleanup paths, because a stuck client will not come back on its own:
   - Any EXISTING registration is unregistered here directly, and its caches
     dropped, so a browser holding an old worker is cleaned on its next page view
     even if it never re-fetches /sw.js.
   - /sw.js itself still responds, with a worker that unregisters itself (see
     PwaController). Returning a 404 instead would leave some browsers holding the
     old worker indefinitely.

   The manifest and icons stay, so add-to-home-screen still works.
*/ ?>
<script>
(function () {
    if (!('serviceWorker' in navigator)) { return; }

    navigator.serviceWorker.getRegistrations().then(function (rs) {
        if (!rs.length) { return null; }
        return Promise.all(rs.map(function (r) { return r.unregister(); })).then(function () {
            return window.caches ? caches.keys().then(function (ks) {
                return Promise.all(ks.map(function (k) { return caches.delete(k); }));
            }) : null;
        }).then(function () {
            // Only reload if a worker was actually controlling this page — the
            // page in front of the user right now may have been served BY it.
            // Session-flagged so this can never loop.
            if (!navigator.serviceWorker.controller) { return; }
            try {
                if (sessionStorage.getItem('ot_sw_cleared')) { return; }
                sessionStorage.setItem('ot_sw_cleared', '1');
            } catch (e) { return; }
            location.reload();
        });
    }).catch(function () {});
})();
</script>
<script>(function(){var p=location.pathname;if(/^\/(admin|portal|account|t)(\/|$)/.test(p))return;try{fetch('/t?p='+encodeURIComponent(p)+'&r='+encodeURIComponent(document.referrer||''),{cache:'no-store',keepalive:true});}catch(e){}})();</script>
