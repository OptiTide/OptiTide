<?php

namespace App\Models;

use App\Core\Model;

class BlacklistTarget extends Model
{
    protected static string $table = 'blacklist_targets';

    public const TYPE_DOMAIN = 'domain';
    public const TYPE_IP = 'ip';

    public const STATUS_OK = 'ok';
    public const STATUS_LISTED = 'listed';
    public const STATUS_UNKNOWN = 'unknown';
}
