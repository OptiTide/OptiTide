<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

class RedirectIfAuthenticated implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect(Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
        }

        return $next($request);
    }
}
