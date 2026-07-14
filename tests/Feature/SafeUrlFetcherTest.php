<?php

use App\Support\SafeUrlFetcher;
use App\Support\UnsafeUrlException;
use Illuminate\Support\Facades\Http;

/** A fetcher with deterministic, hermetic host resolution (no real DNS). */
class TestFetcher extends SafeUrlFetcher
{
    /** @var array<string, array<int, string>> */
    public array $map = [];

    protected function resolve(string $host): array
    {
        return $this->map[$host] ?? [];
    }
}

function fetcher(array $map = [], int $maxBytes = 2_000_000): TestFetcher
{
    $f = new TestFetcher(maxRedirects: 3, maxBytes: $maxBytes);
    $f->map = $map;

    return $f;
}

// ---------------------------------------------------------------------------
// Scheme + literal-IP rejections (no DNS/HTTP needed)
// ---------------------------------------------------------------------------

test('non-http schemes are rejected', function (string $url) {
    expect(fn () => (new SafeUrlFetcher)->fetch($url))->toThrow(UnsafeUrlException::class);
})->with([
    'ftp://example.com/x',
    'file:///etc/passwd',
    'gopher://example.com/',
    'data:text/html,<script>',
]);

test('loopback, private, link-local and metadata IP literals are rejected', function (string $ip) {
    expect(fn () => (new SafeUrlFetcher)->fetch("http://{$ip}/"))->toThrow(UnsafeUrlException::class);
})->with([
    '127.0.0.1',
    '127.5.5.5',
    '10.0.0.5',
    '172.16.9.9',
    '192.168.1.1',
    '169.254.169.254',   // cloud metadata
    '100.64.0.1',        // CGNAT
    '0.0.0.0',
    '[::1]',             // IPv6 loopback
    '[::ffff:127.0.0.1]', // IPv4-mapped loopback
    '[64:ff9b::a9fe:a9fe]', // NAT64 embedding 169.254.169.254 (metadata)
    '[64:ff9b::7f00:1]',    // NAT64 embedding 127.0.0.1
    '[2002:7f00:1::]',      // 6to4 embedding 127.0.0.1
    '[::7f00:1]',           // IPv4-compatible embedding 127.0.0.1
    '[fec0::1]',            // site-local
    '[ff02::1]',            // multicast
    '[fc00::1]',            // unique-local
]);

test('non-standard ports are rejected', function (string $url) {
    expect(fn () => (new SafeUrlFetcher)->fetch($url))->toThrow(UnsafeUrlException::class);
})->with([
    'http://93.184.216.34:22/',
    'http://93.184.216.34:8080/',
    'http://93.184.216.34:6379/',
]);

// ---------------------------------------------------------------------------
// DNS-resolution rejections
// ---------------------------------------------------------------------------

test('a host resolving to a private IP is rejected', function () {
    expect(fn () => fetcher(['evil.test' => ['10.0.0.5']])->fetch('http://evil.test/'))
        ->toThrow(UnsafeUrlException::class);
});

test('a host with any private A record is rejected (mixed records)', function () {
    expect(fn () => fetcher(['mixed.test' => ['93.184.216.34', '127.0.0.1']])->fetch('http://mixed.test/'))
        ->toThrow(UnsafeUrlException::class);
});

test('a host that does not resolve is rejected', function () {
    expect(fn () => fetcher(['nowhere.test' => []])->fetch('http://nowhere.test/'))
        ->toThrow(UnsafeUrlException::class);
});

// ---------------------------------------------------------------------------
// Redirect re-validation
// ---------------------------------------------------------------------------

test('a redirect to a private address is re-validated and rejected', function () {
    Http::fake(['*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/'])]);

    expect(fn () => fetcher(['good.test' => ['93.184.216.34']])->fetch('http://good.test/'))
        ->toThrow(UnsafeUrlException::class);
});

test('too many redirects is rejected', function () {
    Http::fake(['*' => Http::response('', 302, ['Location' => '/loop'])]);

    expect(fn () => fetcher(['good.test' => ['93.184.216.34']])->fetch('http://good.test/'))
        ->toThrow(UnsafeUrlException::class, 'Too many redirects.');
});

// ---------------------------------------------------------------------------
// Happy path + size cap
// ---------------------------------------------------------------------------

test('a public host is fetched and its body returned', function () {
    Http::fake(['*' => Http::response('<html><title>Hello</title></html>', 200)]);

    $body = fetcher(['good.test' => ['93.184.216.34']])->fetch('http://good.test/');

    expect($body)->toContain('Hello');
});

test('the response body is capped at maxBytes', function () {
    Http::fake(['*' => Http::response(str_repeat('a', 5000), 200)]);

    $body = fetcher(['good.test' => ['93.184.216.34']], maxBytes: 100)->fetch('http://good.test/');

    expect(strlen($body))->toBe(100);
});
