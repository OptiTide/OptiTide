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

class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth.login', ['title' => 'Sign In']);
    }

    public function login(Request $request): Response
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Persistent, IP-keyed throttle (server-side, so it can't be reset by
        // dropping the session cookie): 10 failed attempts per minute per IP.
        $throttleKey = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            Session::flash('error', 'Too many attempts. Please wait a minute and try again.');

            return $this->back();
        }

        $user = Auth::validateCredentials((string) $request->input('email'), (string) $request->input('password'));

        if ($user === null) {
            RateLimiter::hit($throttleKey, 60);
            Session::flash('error', 'Those credentials do not match our records.');
            Session::flash('_old', ['email' => $request->input('email')]);

            return $this->back();
        }

        RateLimiter::clear($throttleKey);

        // Password OK — if 2FA is on, hold the login and go to the challenge.
        $twoFactor = new TwoFactorService();
        if ($twoFactor->enabled($user)) {
            Session::put('_2fa_user', $user['id']);
            if (($user['two_factor_method'] ?? null) === TwoFactorService::METHOD_EMAIL) {
                $twoFactor->sendEmailCode($user);
            }

            return $this->redirect(route('2fa.challenge'));
        }

        Auth::login($user);
        User::updateById(Auth::id(), ['last_login_at' => now()]);

        $intended = Session::pull('_intended');
        if ($intended && Auth::isClient() && ! str_starts_with($intended, '/admin')) {
            return $this->redirect($intended);
        }
        if ($intended && Auth::isStaff() && ! str_starts_with($intended, '/portal')) {
            return $this->redirect($intended);
        }

        return $this->redirect(Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
    }
}
