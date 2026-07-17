<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\Audit\AuditLog;
use App\Services\Mail\Mail;

class EmailVerificationController extends Controller
{
    /** Confirm an email address from the token in the emailed link. */
    public function verify(Request $request, string $token): Response
    {
        $token = trim($token);
        $user  = strlen($token) >= 20
            ? User::query()->where('email_verify_token', $token)->first()
            : null;

        if (! $user) {
            Session::flash('error', 'That confirmation link is invalid or has already been used.');

            return $this->redirect(Auth::check() ? route('portal.dashboard') : route('login'));
        }

        if (empty($user['email_verified_at'])) {
            User::updateById($user['id'], [
                'email_verified_at'  => now(),
                'email_verify_token' => null,
            ]);
            AuditLog::record('user.email_verified', 'user', $user['id'], ['email' => $user['email']]);

            // emails/welcome.php was written, branded, and then wired to nothing. This
            // is where it belongs: the address is confirmed (so it will actually
            // arrive) and they have just landed. Inside the empty() guard, so
            // re-clicking an old link cannot send it a second time.
            try {
                Mail::to($user['email'], $user['name'])
                    ->subject('Welcome to ' . config('company.brand_name'))
                    ->view('emails.welcome', [
                        'name' => $user['name'],
                        'url'  => url('portal'),
                    ])
                    ->send();
            } catch (\Throwable $e) {
                // Confirming an email address must never fail on the welcome note.
                error_log('Welcome email failed: ' . $e->getMessage());
            }
        }

        Session::flash('success', 'Thanks! Your email address is now confirmed. 🎉');

        return $this->redirect(Auth::check() ? route('portal.dashboard') : route('login'));
    }

    /** Resend the confirmation email to the signed-in user. */
    public function resend(Request $request): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $this->redirect(route('login'));
        }

        if (! empty($user['email_verified_at'])) {
            Session::flash('status', 'Your email is already confirmed.');

            return $this->redirect(route('portal.dashboard'));
        }

        $token = $user['email_verify_token'] ?? null;
        if (! $token) {
            $token = bin2hex(random_bytes(24));
            User::updateById($user['id'], ['email_verify_token' => $token]);
        }

        try {
            Mail::to($user['email'], $user['name'])
                ->subject('Confirm your email — ' . config('company.brand_name'))
                ->view('emails.verify-email', [
                    'name' => $user['name'],
                    'url'  => url('email/verify/' . $token),
                ])
                ->send();
            Session::flash('success', 'We\'ve sent a fresh confirmation link to ' . $user['email'] . '.');
        } catch (\Throwable $e) {
            Session::flash('error', 'We couldn\'t send the email right now — please try again shortly.');
        }

        return $this->redirect(route('portal.dashboard'));
    }
}
