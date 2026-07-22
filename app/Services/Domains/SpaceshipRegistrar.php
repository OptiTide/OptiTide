<?php

namespace App\Services\Domains;

/**
 * Spaceship External API — domain availability.
 *
 * Verified against the vendor's published OpenAPI spec (docs.spaceship.dev):
 *   POST {base}/domains/available
 *   headers: X-Api-Key, X-Api-Secret   (raw — the docs state explicitly that
 *            the key and secret are NOT to be encoded, signed or base64'd)
 *   body:    {"domains":[{"domainName":"example.com"}]}   1..20 items
 *   200:     {"domains":[{"domain":..,"result":..,"premiumPricing":[..]?}]}
 *   errors:  application/problem+json with a "detail" string
 *
 * Rate limits are per-operation, not global: availability allows 30 calls per
 * user per 30 seconds, and a SINGLE-domain check is additionally capped at 5
 * per domain per 300 seconds. That second limit is why this only ever uses the
 * batch endpoint — checking eight TLDs as eight single calls would burn the
 * per-domain budget almost immediately.
 *
 * There is NO sandbox. Every call here hits production, which is a further
 * reason this class stays read-only.
 */
final class SpaceshipRegistrar implements DomainRegistrar
{
    /** The API caps a batch at 20 domains. */
    private const MAX_BATCH = 20;

    public function isConfigured(): bool
    {
        return trim((string) config('domains.spaceship.api_key')) !== ''
            && trim((string) config('domains.spaceship.api_secret')) !== '';
    }

    public function checkAvailability(array $domains): array
    {
        $domains = array_values(array_filter(array_map(
            fn ($d) => strtolower(trim((string) $d)),
            $domains
        ), fn ($d) => $d !== ''));

        if ($domains === []) {
            return [];
        }

        if (! $this->isConfigured()) {
            return $this->allUnknown($domains, 'Domain search is not configured yet.');
        }

        $out = [];
        foreach (array_chunk($domains, self::MAX_BATCH) as $chunk) {
            foreach ($this->checkChunk($chunk) as $row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /** @param array<int,string> $chunk */
    private function checkChunk(array $chunk): array
    {
        $payload = ['domains' => array_map(fn ($d) => ['domainName' => $d], $chunk)];

        $handle = curl_init(rtrim((string) config('domains.spaceship.base_url'), '/') . '/domains/available');
        curl_setopt_array($handle, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) config('domains.spaceship.timeout', 15),
            CURLOPT_HTTPHEADER     => [
                'X-Api-Key: ' . config('domains.spaceship.api_key'),
                'X-Api-Secret: ' . config('domains.spaceship.api_secret'),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $transportError = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            logger('Spaceship availability: transport failure.', ['error' => $transportError]);

            return $this->allUnknown($chunk, 'Could not reach the domain registrar.');
        }

        if ($status === 429) {
            // Surfaced distinctly: it is the one failure a visitor can fix by
            // waiting, and the one most likely to show up under real traffic.
            logger('Spaceship availability: rate limited.', ['status' => $status]);

            return $this->allUnknown($chunk, 'Too many domain searches just now — try again in a minute.');
        }

        if ($status >= 300) {
            // Never log the response wholesale — an auth failure can echo back
            // request headers. "detail" is the documented error field.
            $detail = (string) (json_decode((string) $response, true)['detail'] ?? '');
            logger('Spaceship availability: API error.', ['status' => $status, 'detail' => $detail]);

            return $this->allUnknown($chunk, 'The domain registrar returned an error.');
        }

        $decoded = json_decode((string) $response, true);
        if (! is_array($decoded) || ! isset($decoded['domains']) || ! is_array($decoded['domains'])) {
            logger('Spaceship availability: unexpected response shape.', ['status' => $status]);

            return $this->allUnknown($chunk, 'Unexpected response from the domain registrar.');
        }

        // Index the response by domain rather than trusting positional order —
        // the spec does not promise the array returns in the order sent, and a
        // silent off-by-one would tell a customer the wrong name is free.
        $byDomain = [];
        foreach ($decoded['domains'] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $name = strtolower(trim((string) ($entry['domain'] ?? '')));
            if ($name !== '') {
                $byDomain[$name] = $entry;
            }
        }

        $out = [];
        foreach ($chunk as $domain) {
            $entry = $byDomain[$domain] ?? null;

            if ($entry === null) {
                $out[] = ['domain' => $domain, 'available' => false, 'premium' => false, 'reason' => 'No answer for this name.'];
                continue;
            }

            // Treat ONLY an explicit positive as available. An unrecognised
            // value must never fall through to "yes" — telling a customer a
            // taken name is free is the expensive direction to be wrong in.
            $result = strtolower(trim((string) ($entry['result'] ?? '')));
            $available = $result === 'available';

            $out[] = [
                'domain'    => $domain,
                'available' => $available,
                'premium'   => ! empty($entry['premiumPricing']),
                'reason'    => in_array($result, ['available', 'unavailable', 'registered', 'taken'], true)
                    ? ''
                    : ('Registrar said: ' . ($result !== '' ? $result : 'no result')),
            ];
        }

        return $out;
    }

    /** @param array<int,string> $domains */
    private function allUnknown(array $domains, string $reason): array
    {
        return array_map(fn ($d) => [
            'domain'    => $d,
            'available' => false,
            'premium'   => false,
            'reason'    => $reason,
        ], $domains);
    }
}
