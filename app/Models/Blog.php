<?php

namespace App\Models;

use App\Core\Model;

/**
 * A blog / news article. The public site only ever shows published posts whose
 * publish time has passed; drafts and scheduled posts stay hidden. Slugs are
 * generated uniquely from the title so the unique column never collides.
 */
class Blog extends Model
{
    protected static string $table = 'blogs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT     => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
    ];

    /** Published, publish-time-reached posts, newest first. */
    public static function published(?int $limit = null): array
    {
        $q = static::query()
            ->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        if ($limit !== null) {
            $q->limit($limit);
        }

        return $q->get();
    }

    /** A single live post by slug, or null if missing/draft/scheduled. */
    public static function livePost(string $slug): ?array
    {
        $post = static::firstWhere('slug', $slug);

        if (! $post
            || $post['status'] !== self::STATUS_PUBLISHED
            || (string) ($post['published_at'] ?? '') === ''
            || $post['published_at'] > now()) {
            return null;
        }

        return $post;
    }

    /** Distinct non-empty categories among published posts. */
    public static function categories(): array
    {
        $cats = [];
        foreach (static::published() as $post) {
            $c = trim((string) ($post['category'] ?? ''));
            if ($c !== '') {
                $cats[$c] = true;
            }
        }
        ksort($cats);

        return array_keys($cats);
    }

    /** A URL-safe, table-unique slug derived from a title. */
    public static function uniqueSlug(string $title, int|string|null $ignoreId = null): string
    {
        $base = static::slugify($title) ?: 'post';
        $slug = $base;
        $n = 2;

        while (true) {
            $existing = static::firstWhere('slug', $slug);
            if (! $existing || (string) $existing['id'] === (string) $ignoreId) {
                return $slug;
            }
            $slug = $base . '-' . $n++;
        }
    }

    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    /** ~n-minute read estimate from the body length. */
    public static function readingMinutes(?string $body): int
    {
        $words = str_word_count(strip_tags((string) $body));

        return max(1, (int) ceil($words / 200));
    }
}
