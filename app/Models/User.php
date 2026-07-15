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
        self::ROLE_STAFF  => 'Staff',
        self::ROLE_CLIENT => 'Client',
    ];

    /** Case-insensitive lookup so login works regardless of how the email was cased. */
    public static function findByEmail(string $email): ?array
    {
        return static::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
    }

    public static function isStaffRole(?string $role): bool
    {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_STAFF], true);
    }

    /** A user row (array) has a confirmed email address. Staff are implicitly trusted. */
    public static function hasVerifiedEmail(?array $user): bool
    {
        if (! $user) {
            return false;
        }
        if (self::isStaffRole($user['role'] ?? null)) {
            return true;
        }

        return ! empty($user['email_verified_at']);
    }
}
