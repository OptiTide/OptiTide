<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Progressive Web App plumbing. The manifest and service worker are served
 * through routes (not static files) so the correct Content-Type is guaranteed
 * on any host, including Coolify/FrankenPHP.
 */
class PwaController extends Controller
{
    public function manifest(Request $request): Response
    {
        $data = [
            'name'             => config('company.brand_name'),
            'short_name'       => config('company.brand_name'),
            'description'      => 'Web design, SEO, social media & hosting for Australian business.',
            'start_url'        => '/',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'background_color' => '#0D1530',
            'theme_color'      => '#0D1530',
            'icons'            => [
                ['src' => '/assets/img/favicon.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/img/favicon.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/img/mark.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        return Response::make(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            200,
            ['Content-Type' => 'application/manifest+json; charset=UTF-8']
        );
    }

    /**
     * KILL SWITCH. This route no longer installs a service worker — it removes one.
     *
     * The owner reported three times that logging in landed him on the raw /sw.js
     * source. It was "fixed" twice: once in the worker's fetch handler (a55da2f),
     * once by forcing stuck clients to update. It kept happening. The deployed
     * worker is provably correct — a real browser login with it active lands on
     * /admin every time, and the server can only ever redirect to /admin, /portal
     * or a validated intended path; `route()` resolves by exact name, so no code
     * path can emit /sw.js as a Location.
     *
     * Which means the fault lives in browser-held state I cannot see or reach. I
     * am not able to reproduce it, so I cannot honestly claim another patch fixes
     * it. What I CAN do is remove the thing entirely: with no worker registered and
     * no worker to install, there is nothing left to serve a wrong response and
     * nothing to go stale. Logging in is the front door of the business; the offline
     * shell this cached (one stylesheet and two images) is not worth risking it.
     *
     * Serving a self-destructing worker rather than a 404 is deliberate: every
     * browser that already has one installed fetches this on its next update check,
     * whereupon it purges every cache, unregisters itself, and reloads any open tab
     * so the page is served straight from the network. A 404 leaves some browsers
     * holding the old worker indefinitely.
     *
     * There is NO fetch handler here on purpose — even in the window before it
     * unregisters, this worker cannot intercept a single request.
     *
     * The manifest and icons stay, so adding to the home screen still works.
     */
    public function serviceWorker(Request $request): Response
    {
        $js = <<<'JS'
// Self-destructing worker: removes itself and every cache a predecessor left.
// Deliberately has no fetch handler, so it can never intercept a request.
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (e) => {
  e.waitUntil((async () => {
    try {
      const keys = await caches.keys();
      await Promise.all(keys.map((k) => caches.delete(k)));
    } catch (err) { /* cache API unavailable — unregister anyway */ }

    await self.registration.unregister();

    // Reload any open tab so it is served by the network, not by this worker.
    const clients = await self.clients.matchAll({ type: 'window' });
    clients.forEach((c) => { try { c.navigate(c.url); } catch (err) {} });
  })());
});
JS;

        return Response::make($js, 200, [
            'Content-Type'  => 'application/javascript; charset=UTF-8',
            // must-revalidate so a browser cannot sit on a cached copy of the old
            // worker instead of fetching this one.
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function offline(Request $request): Response
    {
        return $this->view('public.offline', ['title' => 'Offline']);
    }
}
