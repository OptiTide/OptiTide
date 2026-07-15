<?php

namespace App\Core;

/**
 * File-ordered migration runner. Each file in database/migrations returns an
 * object exposing up() and (optionally) down(). Applied migrations are tracked
 * in the "migrations" table by filename.
 */
final class Migrator
{
    public function __construct(protected string $path)
    {
    }

    public function run(callable $output): void
    {
        $this->ensureTable();
        $applied = $this->appliedFilenames();
        $batch = $this->nextBatch();
        $ran = 0;

        foreach ($this->files() as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;

            // Run each migration + its bookkeeping row in one transaction. On
            // Postgres/SQLite (transactional DDL) a failure rolls the table
            // creation back cleanly, so a partially-applied migration is always
            // re-runnable rather than wedging the schema.
            Database::instance()->transaction(function () use ($migration, $name, $batch) {
                $migration->up();

                Database::instance()->table('migrations')->insert([
                    'migration' => $name,
                    'batch'     => $batch,
                    'ran_at'    => now(),
                ]);
            });

            $output("  migrated: $name");
            $ran++;
        }

        $output($ran === 0 ? 'Nothing to migrate.' : "Migrated $ran migration(s).");
    }

    public function rollback(callable $output): void
    {
        $this->ensureTable();
        $batch = (int) (Database::instance()->table('migrations')->selectRaw('MAX(batch) AS b')->first()['b'] ?? 0);

        if ($batch === 0) {
            $output('Nothing to roll back.');

            return;
        }

        $rows = Database::instance()->table('migrations')
            ->where('batch', $batch)->orderBy('id', 'desc')->get();

        foreach ($rows as $row) {
            $file = $this->path . '/' . $row['migration'];
            if (is_file($file)) {
                $migration = require $file;
                if (method_exists($migration, 'down')) {
                    $migration->down();
                }
            }
            Database::instance()->table('migrations')->where('id', $row['id'])->delete();
            $output('  rolled back: ' . $row['migration']);
        }
    }

    public function fresh(callable $output): void
    {
        foreach (array_reverse($this->tableNames()) as $table) {
            Schema::dropIfExists($table);
        }
        Schema::dropIfExists('migrations');
        $output('Dropped all tables.');
        $this->run($output);
    }

    protected function tableNames(): array
    {
        $db = Database::instance();

        if ($db->driver() === 'sqlite') {
            $rows = $db->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        } elseif ($db->driver() === 'pgsql') {
            $rows = $db->select("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_type = 'BASE TABLE'");
        } else {
            $rows = $db->select('SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE()');
        }

        return array_column($rows, 'name');
    }

    protected function files(): array
    {
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    protected function appliedFilenames(): array
    {
        return array_column(Database::instance()->table('migrations')->get(), 'migration');
    }

    protected function nextBatch(): int
    {
        $max = Database::instance()->table('migrations')->selectRaw('MAX(batch) AS b')->first()['b'] ?? 0;

        return (int) $max + 1;
    }

    protected function ensureTable(): void
    {
        if (Schema::hasTable('migrations')) {
            return;
        }

        Schema::create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('ran_at', false);
        });
    }
}
