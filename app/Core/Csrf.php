<?php

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (! Session::has('_csrf')) {
            Session::put('_csrf', bin2hex(random_bytes(32)));
        }

        return Session::get('_csrf');
    }

    /** Constant-time comparison against the session token. */
    public static function verify(?string $token): bool
    {
        $stored = Session::get('_csrf');

        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(static::token()) . '">';
    }
}
