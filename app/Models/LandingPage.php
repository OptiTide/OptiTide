<?php

namespace App\Models;

use App\Core\Model;

class LandingPage extends Model
{
    protected static string $table = 'landing_pages';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /**
     * Slugs a landing page may never take.
     *
     * These are live top-level routes. Without this guard a page slugged "services"
     * or "login" would be unreachable at best (the real route wins) — and if route
     * order ever changed, it would shadow a working part of the site instead.
     * Extracted from routes/web.php; keep in step when adding top-level routes.
     */
    public const RESERVED = [
        '2fa', 'about', 'accept-terms', 'admin', 'api', 'api-credits', 'assets',
        'assistant', 'audit-log', 'backlinks', 'blacklists', 'blog', 'blogs',
        'boards', 'broadcast', 'careers', 'chat', 'clients', 'commissions',
        'contact', 'discounts', 'forgot-password', 'health', 'hosting',
        'how-we-work', 'installments', 'invoices', 'login', 'logout', 'manifest',
        'meetings', 'offline', 'order', 'pay', 'portal', 'privacy', 'profile',
        'project', 'quote', 'quotes', 'refer', 'refund', 'register', 'reset-password',
        'robots', 'security', 'services', 'set-currency', 'settings', 'sitemap',
        'support', 'sw', 't', 'terms', 'tickets', 'users', 'visitors', 'webhooks',
    ];

    /** @return array<int,array<string,mixed>> published pages, newest first */
    public static function published(): array
    {
        return static::query()
            ->where('status', self::STATUS_PUBLISHED)
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /** A published page by slug, or null. */
    public static function live(string $slug): ?array
    {
        return static::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('status', self::STATUS_PUBLISHED)
            ->first();
    }

    /** Is this slug usable? (format, not reserved, not already taken) */
    public static function slugAvailable(string $slug, int|string|null $ignoreId = null): bool
    {
        $slug = strtolower(trim($slug));

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }

        if (in_array($slug, self::RESERVED, true)) {
            return false;
        }

        $existing = static::firstWhere('slug', $slug);

        return ! $existing || (string) $existing['id'] === (string) $ignoreId;
    }

    /** Decoded FAQ pairs, always a list of ['q' => , 'a' => ]. */
    public static function faqs(array $page): array
    {
        $raw = json_decode((string) ($page['faqs'] ?? ''), true);

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            $q = trim((string) ($item['q'] ?? ''));
            $a = trim((string) ($item['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $out[] = ['q' => $q, 'a' => $a];
            }
        }

        return $out;
    }
}
