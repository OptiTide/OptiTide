<?php

namespace App\Models;

use App\Core\Model;

/**
 * Someone applying for a role (or a general expression of interest when
 * job_opening_id is null). Rows hold personal data + a CV, so they are staff-only
 * everywhere and the resume is streamed through an authorised controller — never
 * served from the webroot.
 */
class JobApplication extends Model
{
    protected static string $table = 'job_applications';

    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_HIRED = 'hired';

    public const STATUSES = [
        self::STATUS_NEW         => 'New',
        self::STATUS_REVIEWING   => 'Reviewing',
        self::STATUS_SHORTLISTED => 'Shortlisted',
        self::STATUS_REJECTED    => 'Not proceeding',
        self::STATUS_HIRED       => 'Hired',
    ];

    /** Bootstrap badge class per status, for the admin list. */
    public const STATUS_BADGES = [
        self::STATUS_NEW         => 'text-bg-primary',
        self::STATUS_REVIEWING   => 'text-bg-warning',
        self::STATUS_SHORTLISTED => 'text-bg-info',
        self::STATUS_REJECTED    => 'text-bg-secondary',
        self::STATUS_HIRED       => 'text-bg-success',
    ];

    /** Newest first, optionally filtered by role or status. */
    public static function feed(?int $jobId = null, ?string $status = null): array
    {
        $q = static::query();

        if ($jobId !== null) {
            $q->where('job_opening_id', $jobId);
        }
        if ($status !== null && $status !== '' && isset(self::STATUSES[$status])) {
            $q->where('status', $status);
        }

        return $q->orderBy('created_at', 'desc')->get();
    }

    public static function countNew(): int
    {
        return count(static::query()->where('status', self::STATUS_NEW)->get());
    }
}
