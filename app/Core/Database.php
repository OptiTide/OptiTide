<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    protected static ?Database $instance = null;

    protected PDO $pdo;

    protected string $driver;

    /** Nesting depth so transaction() calls can compose via savepoints. */
    protected int $transactionLevel = 0;

    /** @var array<int,callable> Side effects deferred until the outermost commit. */
    protected array $afterCommit = [];

    public static function instance(): self
    {
        return static::$instance ??= new self();
    }

    /** Reset the shared connection (used by the test harness). */
    public static function reset(): void
    {
        static::$instance = null;
    }

    public function __construct()
    {
        $name = config('database.default', 'sqlite');
        $config = config("database.connections.$name");

        if (! $config) {
            throw new RuntimeException("Database connection [$name] is not configured.");
        }

        $this->driver = $config['driver'];

        try {
            $this->pdo = match ($this->driver) {
                'sqlite' => $this->connectSqlite($config),
                'pgsql'  => $this->connectPgsql($config),
                default  => $this->connectMysql($config),
            };
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    protected function connectSqlite(array $config): PDO
    {
        $path = $config['database'];

        if ($path !== ':memory:' && ! preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path)) {
            $path = base_path($path);
        }

        if ($path !== ':memory:') {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            if (! is_file($path)) {
                touch($path);
            }
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        return $pdo;
    }

    protected function connectMysql(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO($dsn, $config['username'], $config['password']);
    }

    protected function connectPgsql(array $config): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );

        $options = [];
        if (! empty($config['sslmode'])) {
            $dsn .= ';sslmode=' . $config['sslmode'];
        }

        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

        if (! empty($config['schema'])) {
            $pdo->exec('SET search_path TO ' . $config['schema']);
        }

        return $pdo;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /** @return array<int,array<string,mixed>> */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** Run raw DDL/PRAGMA. */
    public function statement(string $sql): bool
    {
        return $this->pdo->exec($sql) !== false;
    }

    public function insert(string $sql, array $bindings = []): string
    {
        $this->pdo->prepare($sql)->execute($bindings);

        return $this->pdo->lastInsertId();
    }

    /** @return int affected rows */
    public function affecting(string $sql, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    public function beginTransaction(): void
    {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec('SAVEPOINT trans' . ($this->transactionLevel + 1));
        }
        $this->transactionLevel++;
    }

    public function commit(): void
    {
        if ($this->transactionLevel === 1) {
            $this->pdo->commit();
        } elseif ($this->transactionLevel > 1) {
            $this->pdo->exec('RELEASE SAVEPOINT trans' . $this->transactionLevel);
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);

        // Only once everything is durably committed. Drain to a local first so a
        // callback that itself opens a transaction cannot re-enter this list.
        if ($this->transactionLevel === 0 && $this->afterCommit !== []) {
            $callbacks = $this->afterCommit;
            $this->afterCommit = [];

            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }

    public function rollBack(): void
    {
        // Anything queued is tied to work that is now being undone. Discarding the
        // whole queue is deliberately conservative: dropping an email is recoverable
        // (resend it), sending one for a rolled-back invoice is not.
        $this->afterCommit = [];

        if ($this->transactionLevel === 0) {
            return;
        }

        if ($this->transactionLevel === 1) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } else {
            $this->pdo->exec('ROLLBACK TO SAVEPOINT trans' . $this->transactionLevel);
        }

        $this->transactionLevel = max(0, $this->transactionLevel - 1);
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Run $fn once the OUTERMOST transaction commits — or immediately if none is open.
     *
     * For side effects that cannot be undone. Emailing from inside a transaction is a
     * bug waiting to happen: Client\OrderController::place() wraps invoice creation in
     * a transaction and can then throw DiscountExhaustedException, rolling the invoices
     * back — but an email sent inline has already gone, leaving the client holding an
     * invoice that does not exist. Deferred here, it only sends if the data survives.
     */
    public function afterCommit(callable $fn): void
    {
        if ($this->transactionLevel === 0) {
            $fn();

            return;
        }

        $this->afterCommit[] = $fn;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }
}
