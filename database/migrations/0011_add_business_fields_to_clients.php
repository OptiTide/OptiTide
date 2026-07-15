<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::instance();

        // Nullable strings; unquoted identifiers + VARCHAR(255) are valid on
        // SQLite, MySQL and PostgreSQL alike.
        foreach (['acn', 'website'] as $column) {
            $db->statement("ALTER TABLE clients ADD COLUMN $column VARCHAR(255)");
        }
    }

    public function down(): void
    {
        $db = Database::instance();
        foreach (['website', 'acn'] as $column) {
            try {
                $db->statement("ALTER TABLE clients DROP COLUMN $column");
            } catch (\Throwable $e) {
                // Older SQLite can't drop columns — safe to ignore on rollback.
            }
        }
    }
};
