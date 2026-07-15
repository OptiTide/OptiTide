<?php

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Redis-backed sessions when configured (falls back to files if Redis
        // is unreachable, so a misconfigured cache never locks users out).
        if (config('session.driver') === 'redis') {
            try {
                if (Redis::available()) {
                    session_set_save_handler(new RedisSessionHandler(), true);
                }
            } catch (\Throwable $e) {
                // keep the default file handler
            }
        }

        $secure = str_starts_with(config('app.url', ''), 'https://');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name(config('session.cookie', 'optitide_session'));
        session_start();

        // Age out the previous request's flash data.
        static::ageFlash();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = static::get($key, $default);
        static::forget($key);

        return $value;
    }

    /** Flash a value available on the very next request only. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash']['new'][$key] = $value;
        $_SESSION[$key] = $value;
    }

    public static function reflash(): void
    {
        $old = $_SESSION['_flash']['old'] ?? [];
        foreach ($old as $key) {
            if (isset($_SESSION[$key])) {
                $_SESSION['_flash']['new'][$key] = $_SESSION[$key];
            }
        }
    }

    protected static function ageFlash(): void
    {
        foreach (($_SESSION['_flash']['old'] ?? []) as $key) {
            if (! array_key_exists($key, $_SESSION['_flash']['new'] ?? [])) {
                unset($_SESSION[$key]);
            }
        }

        $_SESSION['_flash']['old'] = array_keys($_SESSION['_flash']['new'] ?? []);
        $_SESSION['_flash']['new'] = [];
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function invalidate(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
