<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Visit;

/**
 * Lightweight first-party visit beacon (no third-party trackers). A tiny script
 * on public pages pings /t; we record the page, referrer and an anonymous
 * per-browser visitor id. Bots are skipped. Returns 204.
 */
class VisitController extends Controller
{
    public function track(Request $request): Response
    {
        $vid = (string) $request->cookie('ot_vid', '');
        if ($vid === '' || strlen($vid) > 64) {
            $vid = str_random(32);
            if (! headers_sent()) {
                setcookie('ot_vid', $vid, [
                    'expires'  => time() + 365 * 86400,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        $ua = substr((string) ($request->header('User-Agent') ?? ''), 0, 300);
        $isBot = $ua !== '' && preg_match('/bot|spider|crawl|slurp|bingpreview|facebookexternalhit/i', $ua);

        if (! $isBot) {
            try {
                Visit::create([
                    'visitor_id' => $vid,
                    'path'       => substr((string) $request->query('p', '/'), 0, 300),
                    'referrer'   => substr((string) $request->query('r', ''), 0, 300) ?: null,
                    'user_agent' => $ua ?: null,
                    'ip'         => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                // never let tracking break a page
            }
        }

        return Response::make('', 204);
    }
}
