<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use Closure;

/** Forces a client who hasn't accepted the Terms to do so before using the portal. */
class EnsureTermsAccepted implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ($user['role'] ?? null) === User::ROLE_CLIENT && empty($user['terms_accepted_at'])) {
            $allowed = ['/portal/accept-terms', '/logout', '/impersonate/leave'];
            if (! in_array($request->path(), $allowed, true)) {
                return Response::redirect(route('portal.terms.show'));
            }
        }

        return $next($request);
    }
}
