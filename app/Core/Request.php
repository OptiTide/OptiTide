<?php

namespace App\Core;

final class Request
{
    protected array $query;
    protected array $post;
    protected array $server;
    protected array $files;
    protected array $cookies;
    protected string $method;
    protected array $routeParams = [];

    public function __construct(array $query, array $post, array $server, array $files, array $cookies)
    {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->files = $files;
        $this->cookies = $cookies;

        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');

        // Method spoofing for HTML forms (PUT/PATCH/DELETE via _method).
        if ($method === 'POST' && isset($post['_method'])) {
            $spoofed = strtoupper($post['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        $this->method = $method;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return '/' . trim(rawurldecode($path), '/');
    }

    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->input($key);
        }

        return $result;
    }

    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return $value !== null && $value !== '';
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $file : null;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $this->server[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');

        return str_starts_with((string) $header, 'Bearer ')
            ? substr($header, 7)
            : null;
    }

    /**
     * Decode a JSON request body (for machine/API clients). Cached after first
     * read. Returns [] when the body is empty or not valid JSON.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        static $decoded = null;
        if ($decoded === null) {
            $raw = file_get_contents('php://input') ?: '';
            $parsed = json_decode($raw, true);
            $decoded = is_array($parsed) ? $parsed : [];
        }

        if ($key === null) {
            return $decoded;
        }

        return $decoded[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');

        return str_contains((string) $accept, 'application/json');
    }

    /**
     * Is this a real top-level page view, as opposed to a subresource the browser
     * fetched for a page (a script, the service worker, an analytics beacon)?
     *
     * Used to decide what counts as "the page to come back to". Recording a
     * subresource here is what let a failed login redirect to /sw.js: a browser
     * fetches far more than the document, and every one of those is a GET.
     *
     * Sec-Fetch-Dest is the reliable signal and every current browser sends it
     * (Chrome/Edge 80+, Firefox 90+, Safari 16.4+). Older clients that omit it fall
     * back to "does it accept HTML", which a script or beacon fetch does not. The
     * path blocklist is belt-and-braces for anything that slips past both.
     */
    public static function isPageNavigation(self $request): bool
    {
        if ($request->method() !== 'GET' || $request->wantsJson()) {
            return false;
        }

        $dest = (string) $request->header('Sec-Fetch-Dest', '');
        if ($dest !== '') {
            // 'document' = a top-level navigation. Anything else (script, image,
            // empty for fetch/XHR, worker, …) is a subresource.
            if ($dest !== 'document') {
                return false;
            }
        } elseif (! str_contains((string) $request->header('Accept', ''), 'text/html')) {
            return false;
        }

        return ! self::isNonPagePath($request->path());
    }

    /** Endpoints that are never a place a human should be sent back to. */
    public static function isNonPagePath(string $path): bool
    {
        // Anything with a file extension is an asset, not a page — this is what
        // excludes /sw.js, /manifest.webmanifest, /robots.txt, /sitemap.xml.
        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return true;
        }

        foreach (['/assets', '/t', '/offline', '/logout', '/chat', '/api', '/webhooks', '/broadcasting'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->header('X-Forwarded-Proto') === 'https')
            || str_starts_with(config('app.url', ''), 'https://');
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }
}
