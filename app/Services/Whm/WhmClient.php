<?php

namespace App\Services\Whm;

interface WhmClient
{
    /** Whether a real connection is configured (creds present). */
    public function available(): bool;

    /**
     * List reseller accounts.
     *
     * @return array<int,array{domain:string,user:string,plan:?string,status:string,ip:?string,disk_used_mb:?int,disk_limit_mb:?int,server:?string}>
     */
    public function listAccounts(): array;

    /** A one-time cPanel login URL for a hosting account, or null if unavailable. */
    public function createCpanelSession(string $username): ?string;

    /** Suspend a hosting account (returns true on success / when a real call was made). */
    public function suspendAccount(string $username, string $reason = ''): bool;

    /** Unsuspend a hosting account. */
    public function unsuspendAccount(string $username): bool;
}
