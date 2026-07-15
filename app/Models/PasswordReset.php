<?php

namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    protected static bool $timestamps = false;
}
