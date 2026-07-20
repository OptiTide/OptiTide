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

    public function serviceWorker(Request $request): Response
    {
        $js = <<<'JS'
const CACHE = 'optitide-v11';
// Only static, safe-to-cache assets are precached. HTML pages are NEVER cached
// (see the fetch handler) so a stale/auth/redirect page can never leak across
// URLs after a login redirect.
const SHELL = ['/offline', '/assets/css/app.css', '/assets/img/logo.png', '/assets/img/favicon.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((c) => Promise.allSettled(SHELL.map((u) => c.add(u)))).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      // Drop EVERY cache but the current one. Bumping the version is what makes
      // this run: an older worker that cached HTML (which is how logging in could
      // land on the raw /sw.js source) has its entire cache dropped the moment
      // this version activates, so a stale page cannot be served again.
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => caches.open(CACHE))
      // Belt and braces: purge anything HTML-ish or non-/assets/ that a previous
      // version may have put in THIS cache name. Only static assets and the
      // offline shell are ever legitimate entries here.
      .then((c) => c.keys().then((reqs) => Promise.all(reqs.map((r) => {
        const p = new URL(r.url).pathname;
        return (p.startsWith('/assets/') || p === '/offline') ? null : c.delete(r);
      }))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Static assets: stale-while-revalidate. Only cache genuine 200 responses
  // (never redirects/errors/opaque), so we never serve back a bad asset.
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith(
      caches.open(CACHE).then((c) => c.match(req).then((hit) => {
        const network = fetch(req).then((res) => {
          if (res && res.ok && res.type === 'basic') { c.put(req, res.clone()); }
          return res;
        }).catch(() => hit);
        return hit || network;
      }))
    );
    return;
  }

  // Page navigations: ALWAYS go to the network (never cache, never serve a
  // cached page). Only fall back to the offline page when the network is down.
  // This makes it impossible for the SW to return the wrong page (e.g. the raw
  // /sw.js or a stale dashboard) after a login redirect.
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline')));
    return;
  }

  // Everything else (the /sw.js update check, the manifest, tracking pings,
  // API calls): pass straight through, untouched and uncached.
});
JS;

        return Response::make($js, 200, [
            'Content-Type'  => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function offline(Request $request): Response
    {
        return $this->view('public.offline', ['title' => 'Offline']);
    }
}
