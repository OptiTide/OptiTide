<?php

namespace App\Services\Whm;

/** Fail-closed client used when WHM credentials are not configured. */
final class NullWhmClient implements WhmClient
{
    public function available(): bool
    {
        return false;
    }

    public function listAccounts(): array
    {
        return [];
    }

    public function createCpanelSession(string $username): ?string
    {
        return null;
    }

    public function suspendAccount(string $username, string $reason = ''): bool
    {
        return false;
    }

    public function unsuspendAccount(string $username): bool
    {
        return false;
    }

    public function createAccount(string $username, string $domain, string $plan, string $contactEmail): bool
    {
        return false;
    }

    public function terminateAccount(string $username): bool
    {
        return false;
    }

    public function changePackage(string $username, string $plan): bool
    {
        return false;
    }

    public function changePassword(string $username, string $password): bool
    {
        return false;
    }

    public function listPackages(): array
    {
        return [];
    }

    public function lastError(): ?string
    {
        return 'WHM is not connected — set WHM_HOST, WHM_USERNAME and WHM_API_TOKEN first.';
    }
}
