<?php

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

class VerifyCsrf implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (! Csrf::verify(is_string($token) ? $token : null)) {
            throw new HttpException(419, 'Your session has expired. Please refresh and try again.');
        }

        return $next($request);
    }
}
