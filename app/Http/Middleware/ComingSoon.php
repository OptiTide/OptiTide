<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects public traffic to the /coming-soon holding page while the site is
 * pre-launch (COMING_SOON=true). Off by default so it never hides content or
 * breaks the audit lead-magnet unless deliberately switched on.
 *
 * Bypass, so you can preview the real site: (1) any signed-in staff member, or
 * (2) visit /?preview=<COMING_SOON_SECRET> once to drop a bypass cookie.
 */
class ComingSoon
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('coming-soon.enabled')) {
            return $next($request);
        }

        // Never gate: the holding page itself, the free-audit lead capture, the
        // Filament panels + auth, Livewire, built assets, webhooks, health, files.
        if ($request->is(
            'coming-soon',
            'seo-audit',
            'admin', 'admin/*',
            'client', 'client/*',
            'livewire/*',
            'build/*',
            'stripe/*',
            'broadcasting/*',
            'storage/*',
            'up',
        )) {
            return $next($request);
        }

        // Staff always work on the live site.
        $user = $request->user();
        if ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
            return $next($request);
        }

        // Preview bypass via ?preview=<secret> → cookie.
        $secret = config('coming-soon.secret');
        if ($secret) {
            if ($request->query('preview') === $secret) {
                return redirect($request->fullUrlWithoutQuery('preview'))
                    ->withCookie(cookie('site_preview', $secret, 60 * 24 * 30));
            }
            if ($request->cookie('site_preview') === $secret) {
                return $next($request);
            }
        }

        return redirect()->route('coming-soon');
    }
}
