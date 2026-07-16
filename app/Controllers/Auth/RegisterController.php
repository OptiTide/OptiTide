<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLog;
use App\Services\Mail\Mail;
use App\Services\Referrals\ReferralService;
use App\Support\Captcha;

class RegisterController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth.register', [
            'title'   => 'Create an Account',
            'captcha' => Captcha::question(),
        ]);
    }

    public function register(Request $request): Response
    {
        // Same in-house defences as the public forms: honeypot, rate limit and
        // our own arithmetic captcha — no third-party service, no API key.
        if ($request->filled('website')) {
            Session::flash('success', 'Thanks — please check your email to confirm your account.');

            return $this->redirect(route('login'));
        }

        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Session::flash('error', 'Too many sign-up attempts from this connection. Please try again later.');

            return $this->redirect(route('register'));
        }

        $data = $this->validate($request, [
            'name'          => 'required|max:120',
            'business_name' => 'required|max:160',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:8|confirmed',
            'accept_terms'  => 'required',
            'captcha'       => 'required',
        ], ['business_name' => 'Business name', 'accept_terms' => 'Terms acceptance']);

        // Checked after field validation so a field error never silently
        // consumes the single-use challenge.
        if (! Captcha::verify($request->input('captcha'))) {
            Session::flash('error', 'The quick-check answer was incorrect — please try again.');

            return $this->redirect(route('register'));
        }

        RateLimiter::hit($key, 3600);

        $verifyToken = bin2hex(random_bytes(24));

        $user = Database::instance()->transaction(function () use ($data, $verifyToken) {
            $client = Client::create([
                'business_name' => $data['business_name'],
                'contact_name'  => $data['name'],
                'email'         => strtolower($data['email']),
                'status'        => Client::STATUS_ACTIVE,
            ]);

            return User::create([
                'name'          => $data['name'],
                'email'         => strtolower($data['email']),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'              => User::ROLE_CLIENT,
                'client_id'         => $client['id'],
                'status'            => 'active',
                'terms_accepted_at' => now(),
                'referral_code'     => ReferralService::generateUniqueCode(),
                'email_verify_token' => $verifyToken,
            ]);
        });

        Auth::login($user);

        // First-touch referral attribution (from the /r/{code} cookie or ?ref=).
        $refCode = $request->cookie(config('affiliate.cookie_name', 'ot_ref')) ?? $request->query('ref');
        (new ReferralService())->attach($user, is_string($refCode) ? $refCode : null);
        if (! headers_sent()) {
            setcookie(config('affiliate.cookie_name', 'ot_ref'), '', time() - 3600, '/');
        }

        try {
            Mail::to($user['email'], $user['name'])
                ->subject('Confirm your email — ' . config('company.brand_name'))
                ->view('emails.verify-email', [
                    'name' => $user['name'],
                    'url'  => url('email/verify/' . $verifyToken),
                ])
                ->send();
        } catch (\Throwable $e) {
            // never block sign-up on the verification mail
        }

        AuditLog::record('user.registered', 'user', $user['id'], ['email' => $user['email']]);
        Session::flash('success', 'Welcome to ' . config('company.brand_name') . '! We\'ve emailed you a link to confirm your email address.');

        return $this->redirect(route('portal.dashboard'));
    }
}
