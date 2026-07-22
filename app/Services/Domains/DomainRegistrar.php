<?php

namespace App\Services\Domains;

/**
 * A domain registrar we can ask about availability.
 *
 * Deliberately narrow. Registration, renewal and transfer are NOT on this
 * interface yet: Spaceship publishes no sandbox, so the first call that
 * registers a name spends real money against the live account with no way to
 * rehearse it. That belongs behind its own explicit confirmation flow, not
 * behind a method any future caller could reach by accident.
 */
interface DomainRegistrar
{
    /**
     * Check availability for a batch of fully-qualified domain names.
     *
     * @param  array<int,string> $domains e.g. ['optitide.com.au', 'optitide.io']
     * @return array<int,array{domain:string,available:bool,premium:bool,reason:string}>
     *         One entry per input, in the same order. `reason` is empty on a
     *         clean answer and carries the failure text when availability could
     *         not be determined — callers must not read `available` as "yes"
     *         when `reason` is set.
     */
    public function checkAvailability(array $domains): array;

    /** True when real credentials are configured and calls will be attempted. */
    public function isConfigured(): bool;
}
