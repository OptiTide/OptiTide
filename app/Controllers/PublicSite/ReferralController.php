<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Referrals\ReferralService;
use App\Support\Features;

class ReferralController extends Controller
{
    /** /r/{code} — set the first-touch referral cookie, then land on the site. */
    public function capture(Request $request, string $code): Response
    {
        // Referral links already in the wild must still land the visitor on the
        // site — just without attribution, and without a promise we won't honour.
        if (! Features::enabled('affiliate')) {
            return $this->redirect(route('home'));
        }

        $referrer = ReferralService::referrerByCode($code);

        if ($referrer) {
            $days = max(1, (int) config('affiliate.cookie_days', 60));
            $secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            if (! headers_sent()) {
                setcookie(config('affiliate.cookie_name', 'ot_ref'), (string) $referrer['referral_code'], [
                    'expires'  => time() + $days * 86400,
                    'path'     => '/',
                    'secure'   => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            Session::flash('success', 'You followed a referral link — create an account to get started.');
        }

        return $this->redirect(route('home'));
    }
}
