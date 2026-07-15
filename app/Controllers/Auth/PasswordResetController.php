<?php

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Mail\Mail;

class PasswordResetController extends Controller
{
    public function request(Request $request): Response
    {
        return $this->view('auth.forgot-password', ['title' => 'Reset password']);
    }

    public function sendLink(Request $request): Response
    {
        $this->validate($request, ['email' => 'required|email']);
        $email = strtolower((string) $request->input('email'));

        // Always respond the same way to avoid account enumeration.
        if ($user = User::findByEmail($email)) {
            $token = str_random(64);

            PasswordReset::query()->where('email', $email)->delete();
            PasswordReset::create([
                'email'      => $email,
                'token'      => hash('sha256', $token),
                'created_at' => now(),
            ]);

            Mail::to($email, $user['name'])
                ->subject('Reset your OptiTide password')
                ->view('emails.password-reset', [
                    'name' => $user['name'],
                    'url'  => url('reset-password/' . $token . '?email=' . urlencode($email)),
                ])
                ->send();
        }

        Session::flash('status', 'If that email is registered, a reset link is on its way.');

        return $this->back();
    }

    public function reset(Request $request, string $token): Response
    {
        return $this->view('auth.reset-password', [
            'title' => 'Choose a new password',
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): Response
    {
        $data = $this->validate($request, [
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $email = strtolower($data['email']);
        $record = PasswordReset::query()
            ->where('email', $email)
            ->where('token', hash('sha256', $data['token']))
            ->first();

        if (! $record || strtotime($record['created_at']) < time() - 3600) {
            Session::flash('error', 'This reset link is invalid or has expired.');

            return $this->redirect(route('password.request'));
        }

        $user = User::findByEmail($email);
        if ($user) {
            User::updateById($user['id'], ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)]);
        }
        PasswordReset::query()->where('email', $email)->delete();

        Session::flash('success', 'Your password has been reset. Please sign in.');

        return $this->redirect(route('login'));
    }
}
