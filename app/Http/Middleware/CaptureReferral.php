<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-touch referral attribution: when a guest lands with ?ref=CODE, drop an
 * (encrypted) cookie that the registration flow reads to set `referred_by`. Only
 * the FIRST ref wins — a later ?ref can't overwrite an existing attribution.
 */
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ref = $request->query('ref');

        // Referral codes are 8 uppercase alphanumerics (User::generateReferralCode).
        if (is_string($ref)
            && preg_match('/^[A-Z0-9]{8}$/', $ref)
            && ! $request->cookies->has('referral')) {
            // 30-day first-touch window; EncryptCookies (web group) encrypts it.
            $response->headers->setCookie(cookie('referral', $ref, 60 * 24 * 30));
        }

        return $response;
    }
}
