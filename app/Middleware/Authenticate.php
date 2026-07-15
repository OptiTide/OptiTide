<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

class Authenticate implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            Session::put('_intended', $request->path());
            Session::flash('error', 'Please sign in to continue.');

            return Response::redirect(route('login'));
        }

        return $next($request);
    }
}
