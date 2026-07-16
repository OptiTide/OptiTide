<?php

namespace App\Models;

use App\Core\Model;
use App\Support\Money;

/**
 * An advertised role. Admin-managed (see /admin/careers) so openings are posted
 * and closed in-app — the public careers page never hard-codes a job.
 *
 * Only OPEN roles are ever public: a draft is unfinished and a closed role has
 * been filled, so both must stay off the site and out of the sitemap.
 */
class JobOpening extends Model
{
    protected static string $table = 'job_openings';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT  => 'Draft',
        self::STATUS_OPEN   => 'Open',
        self::STATUS_CLOSED => 'Closed',
    ];

    /** schema.org employmentType values, so the JSON-LD stays Google-valid. */
    public const EMPLOYMENT_TYPES = [
        'full_time'  => 'Full-time',
        'part_time'  => 'Part-time',
        'contract'   => 'Contract',
        'casual'     => 'Casual',
        'internship' => 'Internship',
    ];

    public const SCHEMA_EMPLOYMENT_TYPES = [
        'full_time'  => 'FULL_TIME',
        'part_time'  => 'PART_TIME',
        'contract'   => 'CONTRACTOR',
        'casual'     => 'PART_TIME',
        'internship' => 'INTERN',
    ];

    public const WORKPLACE_TYPES = [
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
        'onsite' => 'On-site',
    ];

    public const SALARY_PERIODS = [
        'year'  => 'per year',
        'month' => 'per month',
        'hour'  => 'per hour',
    ];

    /**
     * Live roles, in admin sort order. This is the ONE gate the public list,
     * the sitemap and the detail page all go through — if it disagreed with
     * liveRole(), we'd advertise roles in the sitemap that 404 when clicked.
     * Filtered in PHP so the date semantics are identical to hasClosed() on
     * every driver (SQLite and Postgres differ on date functions).
     */
    public static function open(): array
    {
        $roles = static::query()
            ->where('status', self::STATUS_OPEN)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return array_values(array_filter($roles, fn ($r) => ! self::hasClosed($r)));
    }

    /** All roles for the admin list, newest first within status. */
    public static function ordered(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /** A single OPEN role by slug, or null — the public detail-page gate. */
    public static function liveRole(string $slug): ?array
    {
        $role = static::firstWhere('slug', $slug);

        if (! $role || $role['status'] !== self::STATUS_OPEN) {
            return null;
        }

        if (self::hasClosed($role)) {
            return null;
        }

        return $role;
    }

    /**
     * True once the closing date has PASSED. "Applications close 16 July" means
     * you can apply all day on the 16th, so compare dates — comparing the
     * date-only closes_at against a full 'Y-m-d H:i:s' now() would hide the role
     * at midnight *starting* the closing day and cost us a day of applicants.
     */
    public static function hasClosed(array $role): bool
    {
        $closes = trim((string) ($role['closes_at'] ?? ''));

        return $closes !== '' && substr($closes, 0, 10) < today();
    }

    /** A URL-safe, table-unique slug derived from a title. */
    public static function uniqueSlug(string $title, int|string|null $ignoreId = null): string
    {
        $base = Blog::slugify($title) ?: 'role';
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

    /** "$90,000 – $110,000 per year", or '' when unset/hidden. */
    public static function salaryLabel(array $role): string
    {
        if (empty($role['salary_visible'])) {
            return '';
        }

        $ccy = $role['salary_currency'] ?: 'AUD';
        $min = $role['salary_min_cents'] !== null ? (int) $role['salary_min_cents'] : null;
        $max = $role['salary_max_cents'] !== null ? (int) $role['salary_max_cents'] : null;

        if ($min === null && $max === null) {
            return '';
        }

        // Salaries read as whole dollars — "$90,000", not "$90,000.00". Cents are
        // kept in the DB (house rule) but shown only when they're non-zero.
        $fmt = function (int $c) use ($ccy) {
            $money = (new Money($c, $ccy))->format();

            return $c % 100 === 0 ? substr($money, 0, -3) : $money;
        };
        $period = self::SALARY_PERIODS[$role['salary_period'] ?? 'year'] ?? '';

        $range = match (true) {
            $min !== null && $max !== null && $min !== $max => $fmt($min) . ' – ' . $fmt($max),
            $min !== null                                   => 'From ' . $fmt($min),
            default                                         => 'Up to ' . $fmt($max),
        };

        return trim($range . ' ' . $period);
    }

    /** Split a textarea of one-per-line items into a clean list. */
    public static function lines(?string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) ?: [] as $line) {
            // Tolerate people pasting in "- " or "• " bullets.
            $line = trim(ltrim(trim($line), "-*•\t "));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}
