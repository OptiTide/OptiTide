<?php

namespace App\Services\Seo;

use App\Models\BlacklistTarget;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Services\Audit\AuditLog;
use Closure;

/**
 * DNS blacklist (RBL/DNSBL) monitoring — the checks WHMCS-style hosting panels run.
 *
 * A domain or mail IP is "listed" when the blacklist's DNS zone answers for it:
 * for an IP, reverse the octets and query <reversed>.<zone>; for a domain, query
 * <domain>.<zone>. An A record in 127.0.0.0/8 means listed; NXDOMAIN means clean.
 *
 * When a target gets listed, ONE card is created on the SEO or Hosting board (the
 * owner wanted listings living in the boards, where the work happens). The card
 * guard is the same idempotency shape as the uptime monitor's incident tickets:
 * incident_card_id is only written while null, so a listing that persists across
 * fifty checks still produces exactly one card; it clears on delisting, so a NEW
 * listing later gets a new card.
 *
 * Two deliberate conservatisms:
 * - An unanswerable check (DNS down, blacklist refusing public resolvers) keeps
 *   the PREVIOUS status. A transient failure must not wipe a real listing — the
 *   same lesson as never overwriting ssl_expires_at on a failed handshake.
 * - Spamhaus answers 127.255.255.x when it is refusing to answer (public/open
 *   resolver, volume) — that is a refusal, NOT a listing, and treating it as one
 *   would raise a false alarm on every domain checked through 1.1.1.1/8.8.8.8.
 */
class BlacklistService
{
    /** zone => applies-to type */
    public const ZONES = [
        'zen.spamhaus.org'  => BlacklistTarget::TYPE_IP,
        'bl.spamcop.net'    => BlacklistTarget::TYPE_IP,
        'dnsbl.sorbs.net'   => BlacklistTarget::TYPE_IP,
        'dbl.spamhaus.org'  => BlacklistTarget::TYPE_DOMAIN,
        'multi.surbl.org'   => BlacklistTarget::TYPE_DOMAIN,
        'multi.uribl.com'   => BlacklistTarget::TYPE_DOMAIN,
    ];

    /** @var Closure(string):array<string> resolves a name to A-record IPs ([] = NXDOMAIN) */
    private Closure $resolver;

    public function __construct(?Closure $resolver = null)
    {
        // Injectable so the listing/card logic is testable without live DNS —
        // real DNSBL answers vary by resolver and network, which is exactly the
        // kind of dependency that makes a test suite lie.
        $this->resolver = $resolver ?? function (string $name): array {
            $records = @dns_get_record($name, DNS_A) ?: [];

            return array_values(array_filter(array_column($records, 'ip')));
        };
    }

    /**
     * Check one value against every zone for its type.
     *
     * @return array{listed:?bool, zones:string[]} listed null = no zone answered usably
     */
    public function check(string $type, string $value): array
    {
        $query = $type === BlacklistTarget::TYPE_IP
            ? implode('.', array_reverse(explode('.', $value)))
            : strtolower(trim($value));

        $listedOn = [];
        $usable = 0;

        foreach (self::ZONES as $zone => $forType) {
            if ($forType !== $type) {
                continue;
            }

            $ips = ($this->resolver)($query . '.' . $zone);
            $real = array_filter($ips, fn ($ip) => ! $this->isRefusal($ip));

            // A refusal answer means the zone declined to tell us — it neither
            // confirms nor clears, so it doesn't count as a usable zone.
            if ($ips !== [] && $real === []) {
                continue;
            }

            $usable++;
            if ($real !== []) {
                $listedOn[] = $zone;
            }
        }

        return [
            'listed' => $usable === 0 ? null : $listedOn !== [],
            'zones'  => $listedOn,
        ];
    }

    /**
     * Check every target, reconcile status, and raise/clear board cards.
     *
     * @return array{checked:int, listed:int, new_cards:int, cleared:int, unavailable:int}
     */
    public function run(): array
    {
        $stats = ['checked' => 0, 'listed' => 0, 'new_cards' => 0, 'cleared' => 0, 'unavailable' => 0];

        foreach (BlacklistTarget::all() as $target) {
            $stats['checked']++;
            $result = $this->check((string) $target['type'], (string) $target['value']);

            if ($result['listed'] === null) {
                // No zone answered usably: stamp the attempt, keep the old status.
                BlacklistTarget::updateById($target['id'], ['last_checked_at' => now()]);
                $stats['unavailable']++;
                continue;
            }

            $update = [
                'status'          => $result['listed'] ? BlacklistTarget::STATUS_LISTED : BlacklistTarget::STATUS_OK,
                'listed_on'       => $result['listed'] ? json_encode($result['zones']) : null,
                'last_checked_at' => now(),
            ];

            if ($result['listed']) {
                $stats['listed']++;

                if (empty($target['incident_card_id'])) {
                    $cardId = $this->raiseCard($target, $result['zones']);
                    if ($cardId !== null) {
                        $update['incident_card_id'] = $cardId;
                        $stats['new_cards']++;
                    }
                }
            } elseif (! empty($target['incident_card_id'])) {
                // Delisted: clear the guard so a FUTURE listing opens a fresh card.
                // The old card stays on the board — it is a record of work done,
                // and staff close it by moving it, not by us deleting history.
                $update['incident_card_id'] = null;
                $stats['cleared']++;
                AuditLog::record('blacklist.delisted', 'blacklist_target', $target['id'], ['value' => $target['value']]);
            }

            BlacklistTarget::updateById($target['id'], $update);
        }

        return $stats;
    }

    /**
     * Answers that mean "I won't tell you", not "it's listed".
     *
     * - Spamhaus 127.255.255.x: query refused (open/public resolver, volume, or a
     *   typo'd query). Checking through 1.1.1.1 or 8.8.8.8 gets exactly this, and
     *   reading it as a listing would false-alarm on every domain checked.
     * - URIBL/SURBL 127.0.0.1: their documented "blocked" marker — same story.
     * - Anything OUTSIDE 127.0.0.0/8: a wildcarding ISP resolver answering for
     *   names that don't exist. Only 127.x answers ever mean listed.
     */
    private function isRefusal(string $ip): bool
    {
        if (! str_starts_with($ip, '127.')) {
            return true;
        }

        return $ip === '127.0.0.1' || str_starts_with($ip, '127.255.255.');
    }

    /** Create the incident card on the target's board; null when the board is missing. */
    private function raiseCard(array $target, array $zones): ?int
    {
        $board = Board::byKey((string) ($target['board'] ?: 'hosting'));
        if (! $board) {
            return null;
        }

        $column = BoardColumn::query()->where('board_id', $board['id'])->orderBy('position')->first();
        if (! $column) {
            return null;
        }

        $label = $target['label'] ?: $target['value'];
        $card = BoardCard::create([
            'board_id'  => $board['id'],
            'column_id' => $column['id'],
            'client_id' => $target['client_id'] ?: null,
            'title'     => 'Blacklisted: ' . $label,
            'notes'     => sprintf(
                "%s is listed on: %s\n\nDelisting starts at the blacklist's own removal page. The card clears itself from monitoring once the listing drops; move it to done when the work is.",
                $target['value'],
                implode(', ', $zones)
            ),
            'position'  => BoardCard::nextPosition($column['id']),
        ]);

        AuditLog::record('blacklist.listed', 'blacklist_target', $target['id'], ['value' => $target['value'], 'zones' => $zones]);

        return (int) $card['id'];
    }
}
