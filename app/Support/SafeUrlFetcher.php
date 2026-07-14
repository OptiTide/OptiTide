<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Fetches a USER-SUPPLIED URL with SSRF protections — the guard for the SEO
 * lead magnet, which scrapes an arbitrary prospect URL.
 *
 * Defences:
 *  - scheme allow-list (http/https only);
 *  - resolve the host and reject any private/reserved/loopback/link-local IP
 *    (incl. the 169.254.169.254 cloud metadata endpoint) before connecting;
 *  - pin the validated IP with CURLOPT_RESOLVE so a DNS rebind between the
 *    check and the connection can't retarget an internal host;
 *  - re-validate every redirect hop (no blind redirect following);
 *  - cap connect/read time and response size.
 */
class SafeUrlFetcher
{
    public function __construct(
        protected int $maxRedirects = 3,
        protected int $timeout = 10,
        protected int $connectTimeout = 5,
        protected int $maxBytes = 2_000_000,
    ) {}

    public function fetch(string $url): string
    {
        $current = $url;

        for ($hop = 0; $hop <= $this->maxRedirects; $hop++) {
            [$host, $port] = $this->parse($current);
            $ip = $this->resolveToPublicIp($host);

            try {
                $response = Http::withoutRedirecting()
                    ->timeout($this->timeout)
                    ->connectTimeout($this->connectTimeout)
                    ->withHeaders(['User-Agent' => 'OptiTide-SEO-Auditor/1.0'])
                    ->withOptions([
                        'curl' => [
                            // Connect to the pre-validated IP; DNS can't be re-pointed
                            // at an internal host after our check (rebind defence).
                            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
                            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                            // Bound the download itself so a hostile server can't
                            // stream gigabytes into the worker before the cap runs.
                            CURLOPT_MAXFILESIZE => $this->maxBytes,
                            CURLOPT_NOPROGRESS => false,
                            CURLOPT_XFERINFOFUNCTION => fn ($ch, $dlTotal, $dlNow) => $dlNow > $this->maxBytes ? 1 : 0,
                        ],
                    ])
                    ->get($current);
            } catch (\Throwable $e) {
                // Connection/transport failure, or the size-abort tripped.
                throw new UnsafeUrlException('Could not fetch the URL: '.$e->getMessage());
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (blank($location)) {
                    throw new UnsafeUrlException('Redirect without a Location header.');
                }

                $current = $this->resolveLocation($current, $location);

                continue;
            }

            $body = $response->body();

            return strlen($body) > $this->maxBytes ? substr($body, 0, $this->maxBytes) : $body;
        }

        throw new UnsafeUrlException('Too many redirects.');
    }

    /** @return array{0:string,1:int} [host, port] */
    protected function parse(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException('Only http and https URLs are allowed.');
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new UnsafeUrlException('The URL has no host.');
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        // Standard web ports only — closes the port-scanning SSRF vector.
        if (! in_array($port, [80, 443], true)) {
            throw new UnsafeUrlException("Refusing to connect to non-standard port {$port}.");
        }

        return [$host, $port];
    }

    protected function resolveToPublicIp(string $host): string
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);

        if ($ips === []) {
            throw new UnsafeUrlException("Could not resolve host: {$host}");
        }

        // EVERY resolved address must be public — a host with one public and one
        // private A record must still be refused.
        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new UnsafeUrlException("Refusing to fetch a private or reserved address ({$ip}).");
            }
        }

        return $ips[0];
    }

    /** @return array<int, string> */
    protected function resolve(string $host): array
    {
        $ips = [];

        foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }

        if (array_filter($ips) === []) {
            $ips = @gethostbynamel($host) ?: [];
        }

        return array_values(array_unique(array_filter($ips)));
    }

    protected function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->isPublicIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isPublicIpv6($ip);
        }

        return false;
    }

    protected function isPublicIpv6(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if ($packed === false || strlen($packed) !== 16) {
            return false;
        }

        $bytes = array_values(unpack('C16', $packed));

        // Addresses that embed IPv4 (v4-mapped, v4-compatible, NAT64, 6to4) must
        // be judged by the embedded IPv4 — otherwise 64:ff9b::a9fe:a9fe reaches
        // 169.254.169.254 through a NAT64 gateway. filter_var flags miss these.
        if (($embedded = $this->embeddedIpv4($bytes)) !== null) {
            return $this->isPublicIpv4($embedded);
        }

        $blocked = [
            ['::1', 128],       // loopback
            ['::', 128],        // unspecified
            ['fc00::', 7],      // unique-local
            ['fe80::', 10],     // link-local
            ['fec0::', 10],     // site-local (deprecated)
            ['ff00::', 8],      // multicast
            ['2001:db8::', 32], // documentation
        ];

        foreach ($blocked as [$net, $bits]) {
            if ($this->ipv6InCidr($bytes, $net, $bits)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int,int> $b 16 address bytes */
    protected function embeddedIpv4(array $b): ?string
    {
        $dotted = fn (int $o) => "{$b[$o]}.{$b[$o + 1]}.{$b[$o + 2]}.{$b[$o + 3]}";

        // ::ffff:a.b.c.d — v4-mapped (bytes 0-9 zero, 10-11 = 0xff)
        if (array_sum(array_slice($b, 0, 10)) === 0 && $b[10] === 0xff && $b[11] === 0xff) {
            return $dotted(12);
        }

        // ::a.b.c.d — v4-compatible (bytes 0-11 zero), excluding :: and ::1
        if (array_sum(array_slice($b, 0, 12)) === 0 && array_sum(array_slice($b, 12, 4)) > 1) {
            return $dotted(12);
        }

        // 64:ff9b::a.b.c.d — NAT64 well-known prefix
        if ($b[0] === 0x00 && $b[1] === 0x64 && $b[2] === 0xff && $b[3] === 0x9b
            && array_sum(array_slice($b, 4, 8)) === 0) {
            return $dotted(12);
        }

        // 2002:AABB:CCDD:: — 6to4 (embedded v4 in bytes 2-5)
        if ($b[0] === 0x20 && $b[1] === 0x02) {
            return $dotted(2);
        }

        return null;
    }

    /** @param array<int,int> $bytes */
    protected function ipv6InCidr(array $bytes, string $net, int $bits): bool
    {
        $netBytes = array_values(unpack('C16', inet_pton($net)));
        $fullBytes = intdiv($bits, 8);

        for ($i = 0; $i < $fullBytes; $i++) {
            if ($bytes[$i] !== $netBytes[$i]) {
                return false;
            }
        }

        if ($rem = $bits % 8) {
            $mask = (0xFF << (8 - $rem)) & 0xFF;

            if (($bytes[$fullBytes] & $mask) !== ($netBytes[$fullBytes] & $mask)) {
                return false;
            }
        }

        return true;
    }

    protected function isPublicIpv4(string $ip): bool
    {
        $long = ip2long($ip);

        if ($long === false) {
            return false;
        }

        // Explicit blocklist (RFC 1918/5735/6598/6890) — don't depend on
        // filter_var's flag ambiguity for the ranges that matter for SSRF.
        $blocked = [
            ['0.0.0.0', 8], ['10.0.0.0', 8], ['100.64.0.0', 10], ['127.0.0.0', 8],
            ['169.254.0.0', 16], ['172.16.0.0', 12], ['192.0.0.0', 24], ['192.0.2.0', 24],
            ['192.168.0.0', 16], ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24],
            ['224.0.0.0', 4], ['240.0.0.0', 4], ['255.255.255.255', 32],
        ];

        foreach ($blocked as [$net, $bits]) {
            $mask = $bits === 0 ? 0 : (0xFFFFFFFF << (32 - $bits)) & 0xFFFFFFFF;

            if (($long & $mask) === (ip2long($net) & $mask)) {
                return false;
            }
        }

        return true;
    }

    protected function resolveLocation(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME)) {
            return $location; // absolute
        }

        $parts = parse_url($base);
        $authority = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($location, '/')) {
            return $authority.$location;
        }

        $path = $parts['path'] ?? '/';
        $dir = substr($path, 0, strrpos($path, '/') + 1) ?: '/';

        return $authority.$dir.$location;
    }
}
