<?php

namespace App\Core;

use Closure;

interface Middleware
{
    /**
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}
