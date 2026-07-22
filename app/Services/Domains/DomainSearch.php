<?php

namespace App\Services\Domains;

/**
 * Turns what a customer typed into a set of names to check.
 *
 * Input is user-supplied and reaches an external API, so it is normalised and
 * validated here rather than at the call site: strip a scheme and path if they
 * pasted a URL, drop anything that is not a valid label, and cap how many names
 * one search can produce. The cap is the important one — the availability
 * endpoint allows only 30 calls per user per 30 seconds for the WHOLE account,
 * so an unbounded search box is a way for one visitor to deny domain search to
 * everyone else.
 */
final class DomainSearch
{
    /** Never check more than this many names for one search. */
    private const MAX_RESULTS = 12;

    public function __construct(private DomainRegistrar $registrar)
    {
    }

    public static function make(): self
    {
        return new self(DomainRegistrarFactory::make());
    }

    /**
     * @return array{term:string,results:array<int,array{domain:string,available:bool,premium:bool,reason:string}>,error:string}
     */
    public function search(string $input): array
    {
        $term = $this->normalise($input);

        if ($term === '') {
            return ['term' => '', 'results' => [], 'error' => 'Enter a domain name to check.'];
        }

        // If they typed a full name with a TLD, check exactly that AND offer the
        // same label on the other TLDs. If they typed a bare label, offer the
        // configured list. Either way the thing they actually asked for is first.
        $tlds = (array) config('domains.tlds', ['com.au', 'com']);
        $names = [];

        if (str_contains($term, '.')) {
            $names[] = $term;
            $label = explode('.', $term)[0];
        } else {
            $label = $term;
        }

        foreach ($tlds as $tld) {
            $names[] = $label . '.' . ltrim((string) $tld, '.');
        }

        $names = array_values(array_unique($names));
        $names = array_slice($names, 0, self::MAX_RESULTS);

        return [
            'term'    => $term,
            'results' => $this->registrar->checkAvailability($names),
            'error'   => '',
        ];
    }

    /**
     * "https://My-Site.COM.AU/path" => "my-site.com.au"; junk => "".
     *
     * Rejects rather than repairs anything still invalid after normalising: a
     * silently "fixed" name would have us check something the customer did not
     * ask about and report the answer as if they had.
     */
    private function normalise(string $input): string
    {
        $value = strtolower(trim($input));
        $value = preg_replace('~^[a-z][a-z0-9+.\-]*://~', '', $value) ?? $value;  // scheme
        $value = explode('/', $value)[0];                                          // path
        $value = explode('?', $value)[0];                                          // query
        $value = explode(':', $value)[0];                                          // port
        $value = preg_replace('~^www\.~', '', $value) ?? $value;
        $value = trim($value, ".\t\n\r ");

        if ($value === '' || strlen($value) > 253) {
            return '';
        }

        // Each dot-separated label: alphanumeric and hyphens, not leading or
        // trailing hyphen, 1-63 chars. No IDN handling — a unicode name would
        // need punycode before it could be checked, so reject rather than send
        // something the API will refuse anyway.
        foreach (explode('.', $value) as $label) {
            if (! preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/', $label)) {
                return '';
            }
        }

        return $value;
    }
}
