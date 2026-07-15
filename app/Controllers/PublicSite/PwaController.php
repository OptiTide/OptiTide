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
            'name'             => 'OptiTide',
            'short_name'       => 'OptiTide',
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
const CACHE = 'optitide-v4';
const SHELL = ['/', '/offline', '/assets/css/app.css', '/assets/img/logo.png', '/assets/img/favicon.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Never cache authenticated or payment pages — always go to network.
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/portal') ||
      url.pathname.startsWith('/pay') || url.pathname.startsWith('/account')) {
    return;
  }

  // Static assets: stale-while-revalidate — serve cache fast, refresh in the
  // background so CSS/JS/image updates always land on the next visit.
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith(
      caches.open(CACHE).then((c) => c.match(req).then((hit) => {
        const network = fetch(req).then((res) => { c.put(req, res.clone()); return res; }).catch(() => hit);
        return hit || network;
      }))
    );
    return;
  }

  // Pages: network-first, fall back to cache, then the offline page.
  e.respondWith(
    fetch(req).then((res) => {
      const copy = res.clone();
      caches.open(CACHE).then((c) => c.put(req, copy));
      return res;
    }).catch(() => caches.match(req).then((hit) => hit || caches.match('/offline')))
  );
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
