<?php

namespace App\Support;

use App\Models\User;
use App\Services\ReferralService;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

/**
 * Creates a User from an OAuth identity for filament-socialite's
 * `createUserUsing`. Unlike the package default (name + email only), this:
 *   - marks the email verified (the provider already verified it),
 *   - leaves `password` null (OAuth-only account — the column is nullable),
 *   - lets `role` fall to the DB default 'client', and
 *   - replicates the custom Register page's first-touch referral attribution
 *     (the SSO flow bypasses that page, so it must run here).
 *
 * It only runs for genuinely new emails — filament-socialite's resolveUserUsing
 * links an existing email/password account before ever reaching this creator.
 */
class SocialiteUserCreator
{
    public function __invoke(string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin): User
    {
        $user = User::create([
            'name' => $this->resolveName($oauthUser),
            'email' => $oauthUser->getEmail(),
        ]);

        // email_verified_at is guarded (not mass-assignable) — set it directly.
        // The provider already verified the address.
        $user->email_verified_at = now();
        $user->save();

        app(ReferralService::class)->attachReferral($user, request()->cookie('referral'));

        return $user;
    }

    private function resolveName(SocialiteUserContract $oauthUser): string
    {
        return $oauthUser->getName()
            ?: $oauthUser->getNickname()
            ?: Str::before((string) $oauthUser->getEmail(), '@')
            ?: 'Client';
    }
}
