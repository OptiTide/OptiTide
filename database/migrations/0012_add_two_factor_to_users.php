<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::instance();
        $db->statement('ALTER TABLE users ADD COLUMN two_factor_method VARCHAR(20)');
        $db->statement('ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255)');
        $db->statement('ALTER TABLE users ADD COLUMN two_factor_confirmed_at VARCHAR(30)');
        $db->statement('ALTER TABLE users ADD COLUMN two_factor_recovery_codes TEXT');
    }

    public function down(): void
    {
        $db = Database::instance();
        foreach (['two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_secret', 'two_factor_method'] as $column) {
            try {
                $db->statement("ALTER TABLE users DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore on rollback
            }
        }
    }
};
