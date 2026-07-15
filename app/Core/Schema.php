<?php

namespace App\Core;

final class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $db = Database::instance();
        $blueprint = new Blueprint($table, $db->driver());

        $callback($blueprint);

        $db->statement($blueprint->toCreateSql());

        foreach ($blueprint->indexStatements() as $statement) {
            $db->statement($statement);
        }
    }

    public static function dropIfExists(string $table): void
    {
        $db = Database::instance();
        $char = $db->driver() === 'mysql' ? '`' : '"';
        // CASCADE so Postgres can drop tables referenced by foreign keys.
        $cascade = $db->driver() === 'pgsql' ? ' CASCADE' : '';
        $db->statement('DROP TABLE IF EXISTS ' . $char . $table . $char . $cascade);
    }

    public static function hasTable(string $table): bool
    {
        $db = Database::instance();

        if ($db->driver() === 'sqlite') {
            return $db->selectOne(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
                [$table]
            ) !== null;
        }

        if ($db->driver() === 'pgsql') {
            return $db->selectOne(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = ?',
                [$table]
            ) !== null;
        }

        return $db->selectOne(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        ) !== null;
    }
}
