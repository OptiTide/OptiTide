<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_CLIENT = 'client';

    public const ROLES = [
        self::ROLE_ADMIN  => 'Administrator',
        self::ROLE_STAFF  => 'Staff / VA',
        self::ROLE_CLIENT => 'Client',
    ];

    public static function findByEmail(string $email): ?array
    {
        return static::firstWhere('email', strtolower(trim($email)));
    }

    public static function isStaffRole(?string $role): bool
    {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_STAFF], true);
    }
}
