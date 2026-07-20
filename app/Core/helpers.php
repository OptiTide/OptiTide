<?php

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Support\Money;

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('view')) {
    function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::view($template, $data, $status);
    }
}

if (! function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

if (! function_exists('safe_back_url')) {
    /**
     * Where "go back" should actually send someone.
     *
     * Validated on READ as well as filtered on write, deliberately. Sessions
     * created before the write-side fix still hold a poisoned value (a wrong
     * password would bounce the user to /sw.js or /t), and those sessions are live
     * in people's browsers right now — a write-side fix alone would leave them
     * broken until the session expired. Also stops an off-site redirect.
     */
    function safe_back_url(string $fallback = '/'): string
    {
        $url = Session::get('_previous_url', '');

        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        // Site-relative only — never absolute (https://evil.com) or scheme-relative
        // (//evil.com), both of which leave the site.
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $fallback;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';

        return Request::isNonPagePath($path) ? $fallback : $url;
    }
}

if (! function_exists('back')) {
    function back(): Response
    {
        return Response::redirect(safe_back_url());
    }
}

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        return config('app.url') . '/' . ltrim($path, '/');
    }
}

if (! function_exists('asset')) {
    /**
     * URL for a file under /public/assets, cache-busted with the file's mtime so
     * a new deploy always serves the latest CSS/JS. The ?v= changes the URL,
     * forcing a fresh fetch past the browser AND service-worker caches.
     */
    function asset(string $path): string
    {
        $rel = ltrim($path, '/');
        $file = public_path('assets/' . $rel);
        $version = is_file($file) ? '?v=' . filemtime($file) : '';

        return '/assets/' . $rel . $version;
    }
}

if (! function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return Router::url($name, $params);
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (! function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (! function_exists('request')) {
    function request(): ?Request
    {
        return $GLOBALS['__request'] ?? null;
    }
}

if (! function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_SESSION ?? [];
        }

        return Session::get($key, $default);
    }
}

if (! function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::get('_old', []);

        return $old[$key] ?? $default;
    }
}

if (! function_exists('errors')) {
    function errors(): array
    {
        return Session::get('errors', []);
    }
}

if (! function_exists('error')) {
    function error(string $field): ?string
    {
        return errors()[$field] ?? null;
    }
}

if (! function_exists('has_error')) {
    function has_error(string $field): bool
    {
        return isset(errors()[$field]);
    }
}

if (! function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (! function_exists('today')) {
    function today(): string
    {
        return date('Y-m-d');
    }
}

if (! function_exists('money')) {
    function money(int $minorUnits, ?string $currency = null): Money
    {
        return new Money($minorUnits, $currency ?? config('company.currency', 'AUD'));
    }
}

if (! function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}

if (! function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = preg_replace('~[^\pL\d]+~u', '-', $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('~[^-\w]+~', '', $value);
        $value = trim(strtolower($value), '-');

        return $value ?: 'n-a';
    }
}

if (! function_exists('logger')) {
    function logger(string $message, array $context = []): void
    {
        $line = '[' . now() . '] ' . $message
            . ($context ? ' ' . json_encode($context) : '') . "\n";
        @file_put_contents(storage_path('logs/app.log'), $line, FILE_APPEND);
    }
}

if (! function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        http_response_code(500);
        echo '<pre style="background:#0f172a;color:#e2e8f0;padding:1rem;font-size:.85rem;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit(1);
    }
}
