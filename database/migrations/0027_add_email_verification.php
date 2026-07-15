<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        Database::instance()->statement('ALTER TABLE users ADD COLUMN email_verified_at VARCHAR(30)');
        Database::instance()->statement('ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64)');
    }

    public function down(): void
    {
        foreach (['email_verified_at', 'email_verify_token'] as $column) {
            try {
                Database::instance()->statement("ALTER TABLE users DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
    }
};
