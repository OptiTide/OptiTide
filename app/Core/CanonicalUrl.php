<?php

namespace App\Core;

/**
 * One canonical URL per page, enforced with 301s before anything else runs.
 *
 * Every one of these was a real, measured fault on production:
 *
 *  - https://www.optitide.io/ returned 200. The whole site existed twice, at www
 *    and at the apex, with no redirect between them — textbook duplicate content,
 *    and it splits link equity across two hostnames.
 *  - https://optitide.io/services/ returned "301 -> http://optitide.io/services".
 *    The trailing-slash redirect DOWNGRADED TO HTTP, so every such link took an
 *    extra hop over plaintext before the https redirect caught it.
 *  - /Services returned 404. Mixed-case links from email clients and directories
 *    hit nothing.
 *  - Twelve deleted blog posts returned 404 with no redirect, dumping any
 *    accumulated link equity and giving anyone following an old link a dead end.
 *
 * Runs before routing and before the session so a bot never gets a session cookie
 * on a URL that is about to redirect. One hop only: host, scheme, case and slash
 * are all resolved into a single Location.
 */
final class CanonicalUrl
{
    /**
     * Old URL => where it should go now. Kept explicit rather than pattern-matched
     * because a wrong redirect is worse than a 404: it tells search engines two
     * unrelated pages are the same.
     */
    private const GONE = [
        // The twelve thin duplicates removed in migration 0043. Each points at the
        // long-form article on the same topic that replaced it, so the link equity
        // and any human following an old link both land somewhere useful.
        '/blog/how-much-does-a-website-cost-in-australia-in-2026' => '/blog/how-much-does-a-website-cost-in-australia',
        '/blog/10-signs-your-small-business-website-needs-a-redesign' => '/blog/small-business-website-redesign-signs',
        '/blog/what-is-seo-and-why-does-your-business-need-it' => '/blog/what-is-seo-small-business-guide',
        '/blog/local-seo-how-to-get-your-business-found-on-google-maps' => '/blog/local-seo-google-maps-australia',
        '/blog/how-to-rank-higher-on-google-a-beginner-s-guide' => '/blog/how-to-rank-higher-on-google',
        '/blog/google-business-profile-the-free-tool-every-local-business-needs' => '/blog/google-business-profile-optimisation-guide',
        '/blog/how-often-should-you-post-on-social-media' => '/blog/how-often-to-post-on-social-media',
        '/blog/5-social-media-mistakes-australian-small-businesses-make' => '/blog/social-media-strategy-small-business',
        '/blog/managed-vs-unmanaged-hosting-which-do-you-need' => '/blog/managed-vs-unmanaged-hosting',
        '/blog/why-website-speed-matters-and-how-to-fix-a-slow-site' => '/blog/why-website-speed-matters',
        '/blog/do-you-really-need-ssl-website-security-for-small-business' => '/blog/website-security-ssl-small-business',
        '/blog/how-to-get-more-customers-from-your-website' => '/blog/turn-website-visitors-into-customers',
    ];

    /**
     * Emits a single 301 when the URL is not canonical.
     *
     * Designed so it CANNOT loop. The first version compared HTTP_HOST against the
     * host in APP_URL and redirected on any mismatch — which 301s forever the moment
     * the incoming Host carries a port (`example.com:8080` never equals
     * `example.com`), and would also have redirected the container's own health
     * check at http://127.0.0.1/health, failing it and making Coolify restart-loop
     * the container. Caught both in testing before deploy.
     *
     * So host handling is now an ALLOW-list of one specific alias — `www.` in front
     * of the canonical host — rather than "anything that isn't canonical". An
     * unrecognised host (internal IP, health check, staging domain) is served, never
     * redirected. Path normalisation is host-independent and always safe.
     */
    public static function enforce(): void
    {
        // Only ever redirect GET/HEAD. Redirecting a POST drops the body and turns a
        // form submit into a lost payment or a lost support ticket.
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY);

        // Never touch infrastructure endpoints — the container health check calls
        // /health over plain http on 127.0.0.1, and a redirect there fails the check.
        if ($path === '/health' || str_starts_with($path, '/health/')) {
            return;
        }

        $hostHeader = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = self::stripPort($hostHeader);

        // Internal / loopback callers are infrastructure, not visitors.
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return;
        }

        $canonicalHost = self::canonicalHost();

        // --- path rules (always safe, host-independent) -------------------------
        $target = self::retiredTarget($path);
        $newPath = $target ?? $path;

        // Lowercase the path — but not a retired target (already canonical) and
        // never the query string (tokens and search terms are case-sensitive).
        if ($target === null && $newPath !== strtolower($newPath)) {
            $newPath = strtolower($newPath);
        }

        // Drop a trailing slash, except on the root itself.
        if ($newPath !== '/' && str_ends_with($newPath, '/')) {
            $newPath = rtrim($newPath, '/');
        }

        // --- host rule: ONLY the www alias of the canonical host -----------------
        $wwwAlias = $canonicalHost !== '' && $host === 'www.' . $canonicalHost;
        $targetHost = $wwwAlias ? $canonicalHost : $host;

        // --- scheme rule: only when we can actually tell ------------------------
        // Behind Coolify the app is reached over plain http, so an absent forwarded
        // header means "don't know" — and guessing there is what breaks health
        // checks and internal calls. Only redirect on a header that says http.
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $knownInsecure = $forwarded === 'http';
        $https = self::isHttps();

        if (! $target && $newPath === $path && ! $wwwAlias && ! $knownInsecure) {
            return; // already canonical — the common case, no work done
        }

        $scheme = ($https || $knownInsecure || $wwwAlias) ? 'https' : 'http';
        $location = $scheme . '://' . $targetHost . $newPath;

        // Keep the query string, EXCEPT on a retired URL — those parameters belong
        // to the old page and mean nothing on its replacement.
        if ($query !== null && $query !== '' && $target === null) {
            $location .= '?' . $query;
        }

        // Last line of defence: never redirect a URL to itself.
        $currentScheme = $https ? 'https' : 'http';
        if ($location === $currentScheme . '://' . $hostHeader . $uri) {
            return;
        }

        header('Location: ' . $location, true, 301);
        header('Cache-Control: max-age=3600');
        exit;
    }

    /** example.com:8080 -> example.com */
    private static function stripPort(string $host): string
    {
        // Bracketed IPv6 literals keep their brackets; only a trailing :port goes.
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');

            return $end === false ? $host : substr($host, 0, $end + 1);
        }

        $colon = strrpos($host, ':');

        return $colon === false ? $host : substr($host, 0, $colon);
    }

    /** The one hostname the site should be reachable on, from APP_URL. */
    private static function canonicalHost(): string
    {
        $host = parse_url((string) config('app.url', ''), PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    private static function isHttps(): bool
    {
        // Behind Coolify's proxy the connection to PHP is plain http, so the
        // forwarded header is the only truthful signal.
        return ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    /** Where a retired URL should now point, or null. */
    private static function retiredTarget(string $path): ?string
    {
        $key = $path === '/' ? '/' : rtrim(strtolower($path), '/');

        return self::GONE[$key] ?? null;
    }
}
