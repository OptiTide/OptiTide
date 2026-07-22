<?php

namespace App\Services\Domains;

/**
 * Fail-closed registrar used until real credentials exist.
 *
 * Mirrors NullWhmClient and NullSocialDistributor: reports NOT available with
 * an explanation rather than throwing, so a missing credential degrades the
 * domain search to "we can't check right now" instead of 500ing the page.
 *
 * Never claims a name is free. An optimistic default here would have the site
 * offering customers domains it has not checked and may not be able to
 * register — the one answer that costs money to be wrong about.
 */
final class NullDomainRegistrar implements DomainRegistrar
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function checkAvailability(array $domains): array
    {
        return array_map(fn ($d) => [
            'domain'    => strtolower(trim((string) $d)),
            'available' => false,
            'premium'   => false,
            'reason'    => 'Domain search is not connected yet.',
        ], array_values($domains));
    }
}
