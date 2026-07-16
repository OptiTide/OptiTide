<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLog;
use App\Services\Mail\Mail;
use App\Services\Referrals\ReferralService;

class RegisterController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth.register', ['title' => 'Create an Account']);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'          => 'required|max:120',
            'business_name' => 'required|max:160',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:8|confirmed',
            'accept_terms'  => 'required',
        ], ['business_name' => 'Business name', 'accept_terms' => 'Terms acceptance']);

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
