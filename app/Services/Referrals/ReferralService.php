<?php

namespace App\Services\Referrals;

use App\Models\Referral;
use App\Models\User;
use App\Support\Features;

final class ReferralService
{
    // Unambiguous charset (no 0/O/1/I).
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generateUniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
            }
        } while (User::firstWhere('referral_code', $code));

        return $code;
    }

    /** Return the user's code, generating + persisting one if missing. */
    public static function ensureCode(array $user): string
    {
        if (! empty($user['referral_code'])) {
            return $user['referral_code'];
        }

        $code = self::generateUniqueCode();
        User::updateById($user['id'], ['referral_code' => $code]);

        return $code;
    }

    public static function referrerByCode(?string $code): ?array
    {
        $code = trim((string) $code);

        return $code === '' ? null : User::firstWhere('referral_code', $code);
    }

    /** First-touch attribution: link a newly-registered user to their referrer. */
    public function attach(array $newUser, ?string $code): void
    {
        // A cookie set before the program was switched off must not keep minting
        // relationships that would later earn a commission nobody agreed to.
        if (! Features::enabled('affiliate')) {
            return;
        }

        $referrer = self::referrerByCode($code);
        if (! $referrer) {
            return;
        }
        if ((string) $referrer['id'] === (string) $newUser['id']) {
            return; // no self-referral
        }
        if (Referral::firstWhere('referred_id', $newUser['id'])) {
            return; // already attributed (first touch wins)
        }

        Referral::create([
            'referrer_id' => $referrer['id'],
            'referred_id' => $newUser['id'],
        ]);
    }
}
