<?php

namespace App\Core;

use App\Core\Exceptions\ModelNotFoundException;

/**
 * Lightweight active-record gateway. Rows are plain associative arrays; domain
 * behaviour lives on the concrete model as static methods or in service
 * classes. This keeps the data layer flat and predictable.
 */
abstract class Model
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    protected static bool $timestamps = true;

    public static function query(): QueryBuilder
    {
        return Database::instance()->table(static::$table);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function find(int|string $id): ?array
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function findOrFail(int|string $id): array
    {
        $row = static::find($id);

        if ($row === null) {
            throw new ModelNotFoundException(static::class . " [$id] not found.");
        }

        return $row;
    }

    public static function firstWhere(string $column, mixed $value): ?array
    {
        return static::query()->where($column, $value)->first();
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $orderBy = null): array
    {
        $query = static::query();

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        return $query->get();
    }

    public static function create(array $attributes): array
    {
        if (static::$timestamps) {
            $now = now();
            $attributes['created_at'] ??= $now;
            $attributes['updated_at'] ??= $now;
        }

        $id = static::query()->insert($attributes);

        return static::find($id) ?? array_merge($attributes, [static::$primaryKey => $id]);
    }

    public static function updateById(int|string $id, array $attributes): int
    {
        if (static::$timestamps) {
            $attributes['updated_at'] = now();
        }

        return static::query()->where(static::$primaryKey, $id)->update($attributes);
    }

    public static function deleteById(int|string $id): int
    {
        return static::query()->where(static::$primaryKey, $id)->delete();
    }
}
