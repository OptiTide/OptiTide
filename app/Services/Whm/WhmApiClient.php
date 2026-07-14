<?php

namespace App\Services\Whm;

use Illuminate\Support\Facades\Http;

/**
 * Real WHM API v1 driver. Authenticates with a WHM API token
 * (`Authorization: whm user:token`) against the server's :2087 JSON API.
 */
class WhmApiClient implements WhmClient
{
    public function __construct(
        private readonly string $host,
        private readonly string $username,
        private readonly string $apiToken,
        private readonly int $port = 2087,
    ) {}

    public function isConfigured(): bool
    {
        return true;
    }

    public function serverStatus(): array
    {
        $load = $this->request('loadavg');
        $version = $this->request('version');

        return [
            'load' => [
                (float) ($load['one'] ?? 0),
                (float) ($load['five'] ?? 0),
                (float) ($load['fifteen'] ?? 0),
            ],
            'version' => $version['version'] ?? null,
        ];
    }

    public function listAccounts(): array
    {
        $data = $this->request('listaccts');

        return $data['data']['acct'] ?? $data['acct'] ?? [];
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     */
    private function request(string $function, array $query = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => "whm {$this->username}:{$this->apiToken}",
        ])
            ->timeout(15)
            ->get("https://{$this->host}:{$this->port}/json-api/{$function}", array_merge(['api.version' => 1], $query));

        if ($response->failed()) {
            throw new WhmException("WHM API call `{$function}` failed with HTTP {$response->status()}.");
        }

        return $response->json() ?? [];
    }
}
