<?php

namespace App\Core;

use Predis\Client;

/** Lazy predis client. Never constructed unless a redis driver is selected. */
final class Redis
{
    protected static ?Client $client = null;

    public static function client(): Client
    {
        if (static::$client !== null) {
            return static::$client;
        }

        $config = config('redis');
        $options = ['prefix' => $config['prefix']];

        if (! empty($config['url'])) {
            static::$client = new Client($config['url'], $options);
        } else {
            static::$client = new Client([
                'scheme'   => 'tcp',
                'host'     => $config['host'],
                'port'     => $config['port'],
                'password' => $config['password'],
                'database' => $config['database'],
            ], $options);
        }

        return static::$client;
    }

    /** True if Redis answers a PING — used to fail safely to file drivers. */
    public static function available(): bool
    {
        try {
            return (string) static::client()->ping() !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function reset(): void
    {
        static::$client = null;
    }
}
