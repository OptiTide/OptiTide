<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0D1530">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(config('company.brand_name')) ?>">
<link rel="apple-touch-icon" href="/assets/img/favicon.png">
<?php /*
   Service worker registration, with a self-heal.

   A user reported logging in and landing on the raw /sw.js source. That was fixed
   in the worker itself (a55da2f) — but they hit it AGAIN afterwards, because
   nothing here ever forced a stuck worker to pick the fix up. A browser only
   checks for a new worker on its own schedule, so a client running a broken
   worker from months ago can stay broken indefinitely while the server serves a
   perfectly good one. Fixing the worker is useless if the fix never lands.

   So: check for an update on EVERY page load, and when a new worker takes over,
   reload once so the page is being served by it rather than by the old one.

   The reload is guarded by a session flag — controllerchange fires on the very
   first registration too, and reloading unconditionally would loop forever.

   `?sw=reset` is a deliberate escape hatch: it unregisters every worker and drops
   every cache. It exists so a stuck user can be unstuck over the phone without
   being talked through devtools.
*/ ?>
<script>
(function () {
    if (!('serviceWorker' in navigator)) { return; }

    if (location.search.indexOf('sw=reset') !== -1) {
        navigator.serviceWorker.getRegistrations().then(function (rs) {
            return Promise.all(rs.map(function (r) { return r.unregister(); }));
        }).then(function () {
            return window.caches ? caches.keys().then(function (ks) {
                return Promise.all(ks.map(function (k) { return caches.delete(k); }));
            }) : null;
        }).then(function () { location.replace(location.pathname); });
        return;
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            // Force the check rather than waiting for the browser's own cadence.
            reg.update();
        }).catch(function () {});
    });

    navigator.serviceWorker.addEventListener('controllerchange', function () {
        try {
            if (sessionStorage.getItem('ot_sw_reloaded')) { return; }
            sessionStorage.setItem('ot_sw_reloaded', '1');
        } catch (e) { return; }
        location.reload();
    });
})();
</script>
<script>(function(){var p=location.pathname;if(/^\/(admin|portal|account|t)(\/|$)/.test(p))return;try{fetch('/t?p='+encodeURIComponent(p)+'&r='+encodeURIComponent(document.referrer||''),{cache:'no-store',keepalive:true});}catch(e){}})();</script>
