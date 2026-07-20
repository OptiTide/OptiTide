<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\Audit\AuditLog;
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
        AuditLog::record('auth.login', 'user', $user['id'], ['role' => $user['role']], $user);

        $intended = $this->safeIntended(Session::pull('_intended'));
        if ($intended && Auth::isClient() && ! str_starts_with($intended, '/admin')) {
            return $this->redirect($intended);
        }
        if ($intended && Auth::isStaff() && ! str_starts_with($intended, '/portal')) {
            return $this->redirect($intended);
        }

        return $this->redirect(Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
    }

    /**
     * Only ever bounce a freshly-logged-in user to a real in-app PAGE.
     *
     * This was redirected to with no validation at all. Two reasons that had to
     * change. First, the owner kept landing on the raw /sw.js source after login;
     * I could not prove this was the route, but an unvalidated redirect target is
     * exactly the shape of that bug and it costs nothing to close. Second, and
     * certain: an unvalidated value here is an open redirect — anything that could
     * write a full URL into the session could bounce a user who has just typed
     * their password to an off-site page.
     *
     * Rejects: absolute URLs and scheme-relative ones (//evil.com), anything with
     * a file extension (/sw.js, /manifest.webmanifest, /assets/...), and the known
     * non-page endpoints. Anything that is not plainly an app page falls through
     * to the dashboard, which is always a safe place to land.
     */
    protected function safeIntended(mixed $intended): ?string
    {
        if (! is_string($intended) || $intended === '') {
            return null;
        }

        // Must be a site-relative path, and NOT scheme-relative.
        if (! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return null;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '';

        // A page has no file extension. This is what excludes /sw.js.
        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return null;
        }

        foreach (['/assets/', '/t', '/offline', '/logout', '/login'] as $blocked) {
            if ($path === $blocked || str_starts_with($path, rtrim($blocked, '/') . '/')) {
                return null;
            }
        }

        return $intended;
    }
}
