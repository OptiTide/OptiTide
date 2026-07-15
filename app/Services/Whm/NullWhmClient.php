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
}
