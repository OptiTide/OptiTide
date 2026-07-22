<?php

namespace App\Services\Domains;

/**
 * Resolves the domain registrar, config-gated exactly like WhmClientFactory:
 * the real client ONLY when credentials are actually present, otherwise the
 * fail-closed null driver.
 *
 * Gating on the credentials rather than on the driver name matters — a config
 * that says "spaceship" with an empty key would otherwise produce a client that
 * makes an unauthenticated call to a live API on every search.
 */
final class DomainRegistrarFactory
{
    public static function make(): DomainRegistrar
    {
        if (config('domains.driver') !== 'spaceship') {
            return new NullDomainRegistrar();
        }

        $registrar = new SpaceshipRegistrar();

        return $registrar->isConfigured() ? $registrar : new NullDomainRegistrar();
    }
}
