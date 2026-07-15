<?php

namespace App\Core;

use App\Models\User;

final class Auth
{
    protected static ?array $user = null;
    protected static bool $resolved = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = User::firstWhere('email', strtolower(trim($email)));

        if (! $user || ($user['status'] ?? 'active') !== 'active') {
            // Hash a dummy value to keep timing consistent for unknown emails.
            password_verify($password, '$2y$12$usesomesillystringforsalt................');

            return false;
        }

        if (! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updateById($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        static::login($user);

        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('_auth_id', $user['id']);
        static::$user = $user;
        static::$resolved = true;
    }

    public static function logout(): void
    {
        Session::forget('_auth_id');
        static::$user = null;
        static::$resolved = true;
        Session::regenerate();
    }

    public static function user(): ?array
    {
        if (static::$resolved) {
            return static::$user;
        }

        static::$resolved = true;
        $id = Session::get('_auth_id');
        static::$user = $id ? User::find($id) : null;

        if (static::$user && (static::$user['status'] ?? 'active') !== 'active') {
            static::$user = null;
        }

        return static::$user;
    }

    public static function check(): bool
    {
        return static::user() !== null;
    }

    public static function guest(): bool
    {
        return ! static::check();
    }

    public static function id(): int|string|null
    {
        return static::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return static::user()['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(static::role(), $roles, true);
    }

    public static function isStaff(): bool
    {
        return static::is('admin', 'staff');
    }

    public static function isAdmin(): bool
    {
        return static::is('admin');
    }

    public static function isClient(): bool
    {
        return static::is('client');
    }

    public static function clientId(): int|string|null
    {
        return static::user()['client_id'] ?? null;
    }
}
