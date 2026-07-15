<?php

namespace App\Models;

use App\Core\Model;

class ApiCreditTransaction extends Model
{
    protected static string $table = 'api_credit_transactions';

    /** Append-only ledger rows — only created_at, no updated_at. */
    protected static bool $timestamps = false;
}
