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

    /**
     * Provision a new cPanel account (WHM createacct). The initial password is
     * generated server-side and never stored or shown — access goes through the
     * one-time cPanel SSO session, exactly like every existing account.
     */
    public function createAccount(string $username, string $domain, string $plan, string $contactEmail): bool;

    /** Permanently remove an account and its data (WHM removeacct). */
    public function terminateAccount(string $username): bool;

    /** Move an account to a different hosting package (WHM changepackage). */
    public function changePackage(string $username, string $plan): bool;

    /** Set a new cPanel password for an account (WHM passwd). */
    public function changePassword(string $username, string $password): bool;

    /** @return string[] hosting package names available on this reseller (WHM listpkgs). */
    public function listPackages(): array;

    /**
     * WHM's stated reason for the most recent failed call, or null. WHM rejects
     * things for reasons only it knows ("domain already exists", "package limit
     * reached") — swallowing that leaves the admin guessing.
     */
    public function lastError(): ?string;
}
