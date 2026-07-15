<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        Database::instance()->statement('ALTER TABLE users ADD COLUMN terms_accepted_at VARCHAR(30)');
    }

    public function down(): void
    {
        try {
            Database::instance()->statement('ALTER TABLE users DROP COLUMN terms_accepted_at');
        } catch (\Throwable $e) {
            // older SQLite — ignore
        }
    }
};
