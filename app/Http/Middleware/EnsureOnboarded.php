<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a client with unfinished onboarding to the profile wizard before they
 * can use the rest of the client portal. Registered on the client panel's
 * authMiddleware (runs after Authenticate), so it never affects the storefront
 * or the staff panel.
 *
 * Staff who happen to open /client are exempt (they onboard nowhere), and the
 * wizard page, its Livewire updates, and logout are allow-listed so the guard
 * can't trap the user in a redirect loop or block them from signing out.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Gate on the persisted flag directly (the same predicate the spatie
        // onboarding step uses). spatie's onboarding()->inProgress() memoizes
        // step completion via once(), which would go stale across requests on a
        // persistent worker (FrankenPHP/Octane) — the deployment target.
        if (
            $user instanceof User
            && ! $user->isStaff()
            && ! $user->hasCompletedOnboarding()
            && ! $this->isAllowlisted($request)
        ) {
            return redirect()->route('filament.client.pages.onboarding');
        }

        return $next($request);
    }

    private function isAllowlisted(Request $request): bool
    {
        // The Livewire update route is named `default-livewire.update` here (not
        // the bare `livewire.update`), so match it with a wildcard — otherwise
        // the wizard's own form submit would be redirected mid-flight.
        return $request->routeIs('filament.client.pages.onboarding')
            || $request->routeIs('*livewire.update')
            || $request->routeIs('filament.client.auth.logout');
    }
}
