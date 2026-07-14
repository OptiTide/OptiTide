<?php

namespace App\Http\Controllers;

/**
 * Invokable 404 endpoint. Used to neutralize a third-party package route while
 * keeping `route:cache` working — a Closure route can't be serialized, which
 * fails the production build's `php artisan optimize`.
 */
class NotFoundController extends Controller
{
    public function __invoke(): never
    {
        abort(404);
    }
}
