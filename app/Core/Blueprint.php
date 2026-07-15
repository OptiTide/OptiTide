<?php

namespace App\Core;

/**
 * Portable column definitions for SQLite (dev) and MySQL (prod). Identifiers
 * come only from application code. Indexes and foreign keys are emitted as
 * separate statements so both drivers accept them identically.
 */
final class Blueprint
{
    /** @var string[] */
    protected array $columns = [];

    /** @var string[] */
    protected array $constraints = [];

    /** @var string[] */
    protected array $indexStatements = [];

    public function __construct(public string $table, protected string $driver)
    {
    }

    public function id(string $name = 'id'): void
    {
        $this->columns[] = match ($this->driver) {
            'mysql' => $this->q($name) . ' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => $this->q($name) . ' BIGSERIAL PRIMARY KEY',
            default => $this->q($name) . ' INTEGER PRIMARY KEY AUTOINCREMENT',
        };
    }

    public function string(string $name, int $length = 255, bool $nullable = false, ?string $default = null): void
    {
        $this->addColumn($name, "VARCHAR($length)", $nullable, $default);
    }

    public function text(string $name, bool $nullable = false, ?string $default = null): void
    {
        $this->addColumn($name, 'TEXT', $nullable, $default);
    }

    public function json(string $name, bool $nullable = true): void
    {
        $this->addColumn($name, 'TEXT', $nullable, null);
    }

    public function integer(string $name, bool $nullable = false, ?int $default = null): void
    {
        $this->addColumn($name, $this->driver === 'mysql' ? 'INT' : 'INTEGER', $nullable, $default === null ? null : (string) $default);
    }

    public function bigInteger(string $name, bool $nullable = false, ?int $default = null): void
    {
        $this->addColumn($name, 'BIGINT', $nullable, $default === null ? null : (string) $default);
    }

    public function boolean(string $name, bool $nullable = false, ?bool $default = null): void
    {
        // Stored as 0/1 integers on every driver so app code stays uniform.
        $type = match ($this->driver) {
            'mysql' => 'TINYINT(1)',
            'pgsql' => 'SMALLINT',
            default => 'INTEGER',
        };
        $def = $default === null ? null : ($default ? '1' : '0');
        $this->addColumn($name, $type, $nullable, $def);
    }

    public function timestamp(string $name, bool $nullable = true, ?string $default = null): void
    {
        $type = $this->driver === 'mysql' ? 'DATETIME' : 'TEXT';
        $this->addColumn($name, $type, $nullable, $default, $default === 'CURRENT_TIMESTAMP');
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
    }

    public function foreignId(
        string $name,
        string $references,
        string $onDelete = 'cascade',
        bool $nullable = false
    ): void {
        $type = match ($this->driver) {
            'mysql' => 'BIGINT UNSIGNED',
            'pgsql' => 'BIGINT',
            default => 'INTEGER',
        };
        $this->addColumn($name, $type, $nullable);
        $this->constraints[] = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s',
            $this->q($name),
            $this->q($references),
            $this->q('id'),
            strtoupper($onDelete)
        );
    }

    public function unique(string|array $columns): void
    {
        $columns = (array) $columns;
        $name = 'uniq_' . $this->table . '_' . implode('_', $columns);
        $cols = implode(', ', array_map([$this, 'q'], $columns));
        $this->indexStatements[] = "CREATE UNIQUE INDEX {$this->ifNotExists()}{$this->q($name)} ON {$this->q($this->table)} ($cols)";
    }

    public function index(string|array $columns): void
    {
        $columns = (array) $columns;
        $name = 'idx_' . $this->table . '_' . implode('_', $columns);
        $cols = implode(', ', array_map([$this, 'q'], $columns));
        $this->indexStatements[] = "CREATE INDEX {$this->ifNotExists()}{$this->q($name)} ON {$this->q($this->table)} ($cols)";
    }

    /** MySQL rejects IF NOT EXISTS on CREATE INDEX; SQLite/Postgres accept it. */
    protected function ifNotExists(): string
    {
        return $this->driver === 'mysql' ? '' : 'IF NOT EXISTS ';
    }

    protected function addColumn(string $name, string $type, bool $nullable, ?string $default = null, bool $rawDefault = false): void
    {
        $sql = $this->q($name) . ' ' . $type . ($nullable ? ' NULL' : ' NOT NULL');

        if ($default !== null) {
            $value = $rawDefault || is_numeric($default) ? $default : "'" . str_replace("'", "''", $default) . "'";
            $sql .= ' DEFAULT ' . $value;
        }

        $this->columns[] = $sql;
    }

    public function toCreateSql(): string
    {
        $definitions = array_merge($this->columns, $this->constraints);
        $engine = $this->driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        return 'CREATE TABLE ' . $this->q($this->table) . " (\n    "
            . implode(",\n    ", $definitions)
            . "\n)" . $engine;
    }

    /** @return string[] */
    public function indexStatements(): array
    {
        return $this->indexStatements;
    }

    protected function q(string $identifier): string
    {
        $char = $this->driver === 'mysql' ? '`' : '"';

        return $char . str_replace($char, $char . $char, $identifier) . $char;
    }
}
