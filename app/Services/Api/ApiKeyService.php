<?php

namespace App\Services\Api;

use App\Models\Client;

/**
 * Per-client API keys for the white-label OptiTide API. The plaintext key is
 * shown to the client exactly once; only a SHA-256 hash (plus the last 4 chars
 * for display) is stored, so a database leak can't expose usable keys.
 */
final class ApiKeyService
{
    private const PREFIX = 'otk_';

    /** Generate + store a new key, returning the plaintext (shown once). */
    public function issue(int|string $clientId): string
    {
        $plain = self::PREFIX . bin2hex(random_bytes(24));

        Client::updateById($clientId, [
            'api_key_hash'       => hash('sha256', $plain),
            'api_key_last4'      => substr($plain, -4),
            'api_key_created_at' => now(),
        ]);

        return $plain;
    }

    public function revoke(int|string $clientId): void
    {
        Client::updateById($clientId, [
            'api_key_hash'       => null,
            'api_key_last4'      => null,
            'api_key_created_at' => null,
        ]);
    }

    /** Resolve a presented plaintext key to its client, or null. */
    public function resolveClient(?string $plain): ?array
    {
        $plain = trim((string) $plain);
        if ($plain === '' || ! str_starts_with($plain, self::PREFIX)) {
            return null;
        }

        return Client::query()->where('api_key_hash', hash('sha256', $plain))->first();
    }

    public static function hasKey(array $client): bool
    {
        return ! empty($client['api_key_hash']);
    }

    /** A masked display form, e.g. "otk_••••••1a2b". */
    public static function masked(array $client): string
    {
        return self::PREFIX . '••••••' . ($client['api_key_last4'] ?? '????');
    }
}
