<?php

namespace App\Models;

use App\Core\Model;

class AuditEntry extends Model
{
    protected static string $table = 'audit_logs';

    /** The table has only created_at (immutable log rows), no updated_at. */
    protected static bool $timestamps = false;
}
