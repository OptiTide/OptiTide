<?php

namespace App\Models;

use App\Core\Model;

class Backlink extends Model
{
    protected static string $table = 'backlinks';

    public const STATUS_PROSPECT  = 'prospect';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_LIVE      = 'live';
    public const STATUS_REJECTED  = 'rejected';

    public const STATUSES = [
        self::STATUS_PROSPECT  => 'To do',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_LIVE      => 'Live',
        self::STATUS_REJECTED  => 'Rejected',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PROSPECT  => 'secondary',
        self::STATUS_SUBMITTED => 'info',
        self::STATUS_LIVE      => 'success',
        self::STATUS_REJECTED  => 'danger',
    ];

    public const TYPES = [
        'directory'  => 'Directory',
        'citation'   => 'Local citation',
        'social'     => 'Social profile',
        'guest_post' => 'Guest post',
        'partner'    => 'Partner / link',
        'other'      => 'Other',
    ];
}
