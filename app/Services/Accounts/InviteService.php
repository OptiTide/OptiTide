<?php

namespace App\Services\Accounts;

use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Mail\Mail;
use App\Services\Referrals\ReferralService;

/**
 * Invite a client into the portal.
 *
 * Before this, "add a client" only ran Client::create() — no login, no email. To
 * actually let someone in, an admin had to go to a different screen (Admin > Users),
 * create a user, remember to attach the right client, invent a password, and then
 * send that password to them over something insecure. So in practice nobody got
 * portal access.
 *
 * Now: create the client, tick the box, and they get a link to set their own
 * password. The agency never sees or chooses it.
 */
class InviteService
{
    /** Invites stay valid for a week — a client reads their email that evening, not within the hour. */
    private const TTL_DAYS = 7;

    /**
     * Create (or reuse) the client's portal login and email them a set-password link.
     *
     * Returns null when the email already belongs to somebody else — a staff member,
     * or another client's login. Silently re-pointing an existing account at this
     * client would hand one client access to another's invoices.
     */
    public function invite(array $client, ?string $email = null, ?string $name = null): ?array
    {
        $email = strtolower(trim($email ?: (string) ($client['email'] ?? '')));
        $name = trim($name ?: (string) ($client['contact_name'] ?? $client['business_name'] ?? 'there'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $existing = User::findByEmail($email);

        if ($existing) {
            // Only ever re-invite a login that already belongs to THIS client. Any
            // other match (staff, or another client's user) is refused.
            if ($existing['role'] !== User::ROLE_CLIENT || (string) $existing['client_id'] !== (string) $client['id']) {
                return null;
            }
            $user = $existing;
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                // No password: they choose it from the emailed link. Auth casts a null
                // hash to '' before password_verify, so a passwordless account cannot
                // be logged into with anything — verified, not assumed.
                'password_hash' => null,
                'role' => User::ROLE_CLIENT,
                'client_id' => $client['id'],
                'status' => 'active',
                // The invite link itself proves they control the address, so the
                // portal shouldn't then make them verify it separately.
                'email_verified_at' => now(),
                'terms_accepted_at' => null,
                'referral_code' => ReferralService::generateUniqueCode(),
            ]);
        }

        $this->sendLink($user, $client, $existing !== null);

        return $user;
    }

    /** Mint a fresh token and email it. Public so admins can resend. */
    public function sendLink(array $user, array $client, bool $isResend = false): void
    {
        $token = str_random(64);

        // One live invite per address: drop any earlier token so a forwarded old
        // email can't still set the password after a resend.
        PasswordReset::query()->where('email', $user['email'])->delete();
        PasswordReset::create([
            'email' => $user['email'],
            'token' => hash('sha256', $token),
            'created_at' => now(),
            'expires_at' => date('Y-m-d H:i:s', time() + self::TTL_DAYS * 86400),
        ]);

        Mail::to($user['email'], $user['name'])
            ->subject('Your ' . config('company.brand_name') . ' client portal is ready')
            ->view('emails.client-invite', [
                'name' => $user['name'],
                'business' => $client['business_name'] ?? null,
                'url' => url('reset-password/' . $token . '?email=' . urlencode($user['email'])),
                'days' => self::TTL_DAYS,
                'isResend' => $isResend,
            ])
            ->send();
    }
}
