<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        $db = Database::instance();
        $existing = $this->columns('board_cards');

        // 0015 already gave board_cards a due_date (VARCHAR(30)); adding it again
        // is a hard error on every driver. Only add what is actually missing so
        // this runs clean on the dev SQLite file and on a fresh Postgres alike.
        $additions = [
            'due_date'       => 'VARCHAR(20) NULL',
            'assigned_to'    => 'INTEGER NULL',
            'priority'       => "VARCHAR(20) NOT NULL DEFAULT 'normal'",
            'client_visible' => 'SMALLINT NOT NULL DEFAULT 1',
            'completed_at'   => 'VARCHAR(30) NULL',
        ];

        foreach ($additions as $column => $definition) {
            if (! in_array($column, $existing, true)) {
                $db->statement("ALTER TABLE board_cards ADD COLUMN $column $definition");
            }
        }

        Schema::create('board_card_checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id', 'board_cards');
            $table->string('text', 300);
            $table->boolean('done', false, false);
            $table->integer('position', false, 0);
            $table->timestamps();
            $table->index('card_id');
        });

        Schema::create('board_card_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id', 'board_cards');
            $table->foreignId('user_id', 'users', 'set null', true);
            $table->text('body');
            $table->boolean('is_internal', false, false);
            $table->timestamps();
            $table->index('card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_card_comments');
        Schema::dropIfExists('board_card_checklist');

        // due_date is deliberately not dropped — 0015 owns it, this migration
        // only adopted it.
        foreach (['assigned_to', 'priority', 'client_visible', 'completed_at'] as $column) {
            try {
                Database::instance()->statement("ALTER TABLE board_cards DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
    }

    /** @return string[] lower-cased column names currently on $table */
    protected function columns(string $table): array
    {
        $db = Database::instance();

        $rows = match ($db->driver()) {
            'sqlite' => $db->select("PRAGMA table_info($table)"),
            'pgsql'  => $db->select(
                'SELECT column_name AS name FROM information_schema.columns'
                . ' WHERE table_schema = current_schema() AND table_name = ?',
                [$table]
            ),
            default => $db->select(
                'SELECT column_name AS name FROM information_schema.columns'
                . ' WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            ),
        };

        return array_map('strtolower', array_column($rows, 'name'));
    }
};
