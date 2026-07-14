<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Railway's edge proxy: trust forwarded headers so the app sees
        // the real client IP + https scheme (correct URL generation, secure
        // cookies, and Reverb/OAuth callback URLs). The platform network fronts
        // the container, so trusting all proxies is appropriate here.
        $middleware->trustProxies(at: '*');

        // Stripe signs webhook calls; they carry no CSRF token.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        // Storefront guests authenticate through the Filament client portal.
        $middleware->redirectGuestsTo(fn () => route('filament.client.auth.login'));

        // Resolve the request locale for translatable content, and capture
        // ?ref referral codes for the affiliate program.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CaptureReferral::class,
            \App\Http\Middleware\ComingSoon::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
