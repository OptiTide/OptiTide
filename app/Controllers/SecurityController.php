<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\TwoFactor\TwoFactorService;
use App\Support\QrCode;

class SecurityController extends Controller
{
    protected function layout(): string
    {
        return Auth::isClient() ? 'layouts.portal' : 'layouts.admin';
    }

    public function show(Request $request): Response
    {
        $user = Auth::user();
        $service = new TwoFactorService();
        $enabled = $service->enabled($user);

        $setupMethod = $enabled ? null : Session::get('_2fa_setup_method');
        $setup = null;
        if ($setupMethod === TwoFactorService::METHOD_TOTP && Session::get('_2fa_setup_secret')) {
            $secret = Session::get('_2fa_setup_secret');
            $setup = [
                'secret' => $secret,
                'qr'     => QrCode::svg($service->provisioningUri($secret, $user)),
            ];
        }

        return $this->view('account.security', [
            'title'        => 'Security',
            'layout'       => $this->layout(),
            'user'         => $user,
            'enabled'      => $enabled,
            'method'       => $user['two_factor_method'] ?? null,
            'setup_method' => $setupMethod,
            'setup'        => $setup,
            'recovery'     => session('_2fa_recovery'),
        ]);
    }

    /** Begin enabling a method (generate a TOTP secret, or send an e-mail code). */
    public function setup(Request $request): Response
    {
        $user = Auth::user();
        $service = new TwoFactorService();
        $method = $request->input('method');

        if ($method === TwoFactorService::METHOD_TOTP) {
            Session::put('_2fa_setup_secret', $service->newSecret());
            Session::put('_2fa_setup_method', TwoFactorService::METHOD_TOTP);
        } elseif ($method === TwoFactorService::METHOD_EMAIL) {
            Session::forget('_2fa_setup_secret');
            Session::put('_2fa_setup_method', TwoFactorService::METHOD_EMAIL);
            $service->sendEmailCode($user);
            Session::flash('status', 'We sent a 6-digit code to your e-mail. Enter it below to finish.');
        }

        return $this->redirect(route('security.show') . '#twofactor');
    }

    /** Confirm the code and switch 2FA on; returns one-time recovery codes. */
    public function confirm(Request $request): Response
    {
        $user = Auth::user();
        $service = new TwoFactorService();
        $method = Session::get('_2fa_setup_method');
        $code = (string) $request->input('code');

        $ok = false;
        $secret = null;
        if ($method === TwoFactorService::METHOD_TOTP) {
            $secret = Session::get('_2fa_setup_secret');
            $ok = $secret && $service->verifyTotp($secret, $code);
        } elseif ($method === TwoFactorService::METHOD_EMAIL) {
            $ok = $service->verifyEmailCode($user, $code);
        }

        if (! $ok) {
            Session::flash('error', 'That code is not valid. Please try again.');

            return $this->back();
        }

        $recovery = $service->enable($user, $method, $secret);
        Session::forget('_2fa_setup_secret');
        Session::forget('_2fa_setup_method');
        Session::flash('_2fa_recovery', $recovery);
        Session::flash('success', 'Two-factor authentication is now enabled.');

        return $this->redirect(route('security.show'));
    }

    public function disable(Request $request): Response
    {
        $user = Auth::user();

        if (! password_verify((string) $request->input('current_password'), (string) $user['password_hash'])) {
            Session::flash('error', 'Your current password is incorrect.');

            return $this->back();
        }

        (new TwoFactorService())->disable($user);
        Session::flash('success', 'Two-factor authentication has been disabled.');

        return $this->redirect(route('security.show'));
    }
}
