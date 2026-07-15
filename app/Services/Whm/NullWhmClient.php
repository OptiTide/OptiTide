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
}
