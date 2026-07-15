<?php

namespace App\Services\Whm;

/** Builds the real client only when every credential is present; else fail-closed. */
final class WhmClientFactory
{
    public static function make(): WhmClient
    {
        $c = config('whm');

        if (! empty($c['host']) && ! empty($c['username']) && ! empty($c['api_token'])) {
            return new WhmApiClient(
                (string) $c['host'],
                (int) ($c['port'] ?? 2087),
                (string) $c['username'],
                (string) $c['api_token'],
                (string) ($c['server_label'] ?? 'Primary Server'),
            );
        }

        return new NullWhmClient();
    }
}
