<?php

namespace App\Core;

/**
 * Small fluent query builder. Column/table identifiers are quoted and only
 * ever come from application code (never user input); all values are bound.
 */
class QueryBuilder
{
    protected string $columns = '*';

    /** @var array<int,array{type:string,sql:string}> */
    protected array $wheres = [];

    protected array $bindings = [];

    protected array $orders = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(protected Database $db, protected string $table)
    {
    }

    public function select(string|array $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->columns = implode(', ', array_map([$this, 'wrap'], $columns));

        return $this;
    }

    public function selectRaw(string $expression): static
    {
        $this->columns = $expression;

        return $this;
    }

    public function where(string $column, string $operator, mixed $value = null): static
    {
        // Two-argument shorthand: where('col', $value) => equals.
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['type' => 'basic', 'sql' => $this->wrap($column) . " $operator ?"];
        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if ($values === []) {
            $this->wheres[] = ['type' => 'raw', 'sql' => '0 = 1'];

            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['type' => 'in', 'sql' => $this->wrap($column) . " IN ($placeholders)"];
        array_push($this->bindings, ...array_values($values));

        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = ['type' => 'null', 'sql' => $this->wrap($column) . ' IS NULL'];

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['type' => 'notnull', 'sql' => $this->wrap($column) . ' IS NOT NULL'];

        return $this;
    }

    public function whereLike(string $column, string $value): static
    {
        $this->wheres[] = ['type' => 'like', 'sql' => $this->wrap($column) . ' LIKE ?'];
        $this->bindings[] = $value;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = $this->wrap($column) . ' ' . $direction;

        return $this;
    }

    public function limit(int $value): static
    {
        $this->limit = max(0, $value);

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /** @return array<int,array<string,mixed>> */
    public function get(): array
    {
        return $this->db->select($this->toSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $this->limit(1);

        return $this->db->selectOne($this->toSql(), $this->bindings);
    }

    public function value(string $column): mixed
    {
        $row = $this->select($column)->first();

        return $row[$column] ?? null;
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM ' . $this->wrap($this->table) . $this->compileWheres();
        $row = $this->db->selectOne($sql, $this->bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    public function sum(string $column): int
    {
        $sql = 'SELECT COALESCE(SUM(' . $this->wrap($column) . '), 0) AS aggregate FROM '
            . $this->wrap($this->table) . $this->compileWheres();
        $row = $this->db->selectOne($sql, $this->bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * @return array{data:array,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $total = $this->count();
        $rows = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function insert(array $data): string
    {
        $columns = array_keys($data);
        $wrapped = implode(', ', array_map([$this, 'wrap'], $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = 'INSERT INTO ' . $this->wrap($this->table) . " ($wrapped) VALUES ($placeholders)";

        // Postgres has no lastInsertId() without a sequence name — use RETURNING.
        if ($this->db->driver() === 'pgsql') {
            $row = $this->db->selectOne($sql . ' RETURNING ' . $this->wrap('id'), array_values($data));

            return (string) ($row['id'] ?? '');
        }

        return $this->db->insert($sql, array_values($data));
    }

    public function update(array $data): int
    {
        $set = implode(', ', array_map(fn ($c) => $this->wrap($c) . ' = ?', array_keys($data)));
        $sql = 'UPDATE ' . $this->wrap($this->table) . " SET $set" . $this->compileWheres();
        $bindings = array_merge(array_values($data), $this->bindings);

        return $this->db->affecting($sql, $bindings);
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->wrap($this->table) . $this->compileWheres();

        return $this->db->affecting($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . $this->columns . ' FROM ' . $this->wrap($this->table);
        $sql .= $this->compileWheres();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    protected function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', array_column($this->wheres, 'sql'));
    }

    /** Quote an identifier for the active driver. */
    protected function wrap(string $identifier): string
    {
        if ($identifier === '*' || str_contains($identifier, '(')) {
            return $identifier;
        }

        $char = $this->db->driver() === 'mysql' ? '`' : '"';

        return implode('.', array_map(
            fn ($part) => $part === '*' ? $part : $char . str_replace($char, $char . $char, $part) . $char,
            explode('.', $identifier)
        ));
    }
}
