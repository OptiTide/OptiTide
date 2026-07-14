<?php

namespace App\Services\Whm;

/**
 * Default WHM client: no credentials wired, so it FAILS CLOSED — every call
 * throws rather than pretending the server is reachable. Bound until real WHM
 * credentials (WHM_HOST / WHM_USERNAME / WHM_API_TOKEN) are configured.
 */
class NullWhmClient implements WhmClient
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function serverStatus(): array
    {
        throw $this->notConfigured();
    }

    public function listAccounts(): array
    {
        throw $this->notConfigured();
    }

    private function notConfigured(): WhmException
    {
        return new WhmException(
            'WHM is not configured. Set WHM_HOST, WHM_USERNAME and WHM_API_TOKEN to enable server management.'
        );
    }
}
