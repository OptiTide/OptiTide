<?php

namespace App\Support;

use App\Core\Session;

/**
 * In-house captcha — a simple arithmetic challenge stored in the session. No
 * third-party service, no tracking. Paired with the existing honeypot + rate
 * limiter on the contact form.
 */
final class Captcha
{
    /** Generate a fresh question and remember its answer for this session. */
    public static function question(): string
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        Session::put('_captcha_sum', $a + $b);

        return "What is {$a} + {$b}?";
    }

    /** Verify + consume the answer (single-use). */
    public static function verify(mixed $answer): bool
    {
        $expected = Session::pull('_captcha_sum');

        return $expected !== null && is_numeric($answer) && (int) $answer === (int) $expected;
    }
}
