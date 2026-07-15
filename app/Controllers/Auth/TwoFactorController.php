<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\TwoFactor\TwoFactorService;

class TwoFactorController extends Controller
{
    protected function pendingUser(): ?array
    {
        $id = Session::get('_2fa_user');

        return $id ? User::find($id) : null;
    }

    public function challenge(Request $request): Response
    {
        $user = $this->pendingUser();
        if (! $user) {
            return $this->redirect(route('login'));
        }

        return $this->view('auth.two-factor', [
            'title'  => 'Two-Factor Authentication',
            'method' => $user['two_factor_method'],
        ]);
    }

    public function verify(Request $request): Response
    {
        $user = $this->pendingUser();
        if (! $user) {
            return $this->redirect(route('login'));
        }

        $key = '2fa-verify:' . $user['id'];
        if (RateLimiter::tooManyAttempts($key, 8)) {
            Session::flash('error', 'Too many attempts. Please wait a few minutes and try again.');

            return $this->back();
        }

        if (! (new TwoFactorService())->verifyChallenge($user, (string) $request->input('code'))) {
            RateLimiter::hit($key, 300);
            Session::flash('error', 'That code is not valid. Please try again.');

            return $this->back();
        }

        RateLimiter::clear($key);
        Session::forget('_2fa_user');
        Auth::login($user);
        User::updateById($user['id'], ['last_login_at' => now()]);

        return $this->redirect(Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
    }

    public function resend(Request $request): Response
    {
        $user = $this->pendingUser();
        if ($user && ($user['two_factor_method'] ?? null) === TwoFactorService::METHOD_EMAIL) {
            (new TwoFactorService())->sendEmailCode($user);
            Session::flash('status', 'A new code has been sent to your e-mail.');
        }

        return $this->back();
    }
}
