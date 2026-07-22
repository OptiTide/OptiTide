<?php

namespace App\Services\TwoFactor;

use App\Core\Cache;
use App\Core\Crypt;
use App\Models\User;
use App\Services\Mail\Mail;
use App\Support\Totp;

/**
 * Two-factor authentication: authenticator app (TOTP / "scan") and e-mail code.
 * Recovery codes work with either method so a lost device never locks a user out.
 */
final class TwoFactorService
{
    public const METHOD_TOTP = 'totp';
    public const METHOD_EMAIL = 'email';

    public function enabled(array $user): bool
    {
        return ! empty($user['two_factor_method']) && ! empty($user['two_factor_confirmed_at']);
    }

    // --- TOTP (authenticator app) -----------------------------------------

    public function newSecret(): string
    {
        return Totp::generateSecret();
    }

    public function provisioningUri(string $secret, array $user): string
    {
        return Totp::provisioningUri($secret, $user['email'], (string) config('company.brand_name'));
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        return Totp::verify($secret, $code);
    }

    // --- E-mail codes (cached, 10 min) ------------------------------------

    public function sendEmailCode(array $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->emailKey($user['id']), password_hash($code, PASSWORD_DEFAULT), 600);

        Mail::to($user['email'], $user['name'])
            ->subject('Your ' . config('company.brand_name') . ' verification code')
            ->view('emails.two-factor-code', ['name' => $user['name'], 'code' => $code])
            // The body IS the second factor. Only a bcrypt hash of it is kept
            // above, so letting the email log store the rendered body would make
            // that table the ONLY cleartext copy of a live 2FA code — readable by
            // any admin, and present in every DB dump for the retention window.
            // The log still records that a code was sent, to whom, and whether it
            // delivered; it just does not record the code.
            ->withoutBodyLogging()
            ->send();
    }

    public function verifyEmailCode(array $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        $hash = Cache::get($this->emailKey($user['id']));
        if (! $hash || ! password_verify($code, $hash)) {
            return false;
        }
        Cache::forget($this->emailKey($user['id']));

        return true;
    }

    protected function emailKey(int|string $id): string
    {
        return '2fa:email:' . $id;
    }

    // --- Enable / disable --------------------------------------------------

    /** @return string[] plaintext recovery codes, shown to the user once */
    public function enable(array $user, string $method, ?string $secret = null): array
    {
        $recovery = $this->generateRecoveryCodes();

        User::updateById($user['id'], [
            'two_factor_method'         => $method,
            'two_factor_secret'         => $secret ? Crypt::encrypt($secret) : null,
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => json_encode(array_map(
                fn ($c) => password_hash($c, PASSWORD_DEFAULT),
                $recovery
            )),
        ]);

        return $recovery;
    }

    public function disable(array $user): void
    {
        User::updateById($user['id'], [
            'two_factor_method'         => null,
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    // --- Login challenge ---------------------------------------------------

    public function verifyChallenge(array $user, string $code): bool
    {
        $method = $user['two_factor_method'] ?? null;

        if ($method === self::METHOD_TOTP) {
            $secret = Crypt::decrypt((string) $user['two_factor_secret']);
            if ($secret && Totp::verify($secret, $code)) {
                return true;
            }
        } elseif ($method === self::METHOD_EMAIL) {
            if ($this->verifyEmailCode($user, $code)) {
                return true;
            }
        }

        // Recovery code fallback (single-use).
        return $this->consumeRecoveryCode($user, $code);
    }

    protected function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
        }

        return $codes;
    }

    protected function consumeRecoveryCode(array $user, string $input): bool
    {
        $input = strtoupper(trim($input));
        $hashes = json_decode($user['two_factor_recovery_codes'] ?? '[]', true) ?: [];

        foreach ($hashes as $i => $hash) {
            if (password_verify($input, $hash)) {
                unset($hashes[$i]);
                User::updateById($user['id'], ['two_factor_recovery_codes' => json_encode(array_values($hashes))]);

                return true;
            }
        }

        return false;
    }
}
