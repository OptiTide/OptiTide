<?php

namespace App\Services\Whm;

/**
 * Thin WHM (Web Host Manager) API client for the hosting server. Config-gated:
 * a real driver is bound only when WHM credentials are present, otherwise the
 * fail-closed NullWhmClient is used (mirrors the SocialDistributor pattern).
 */
interface WhmClient
{
    public function isConfigured(): bool;

    /**
     * Server health snapshot.
     *
     * @return array{load: array<int, float>, version: ?string}
     */
    public function serverStatus(): array;

    /**
     * Hosting accounts on the server.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAccounts(): array;
}
