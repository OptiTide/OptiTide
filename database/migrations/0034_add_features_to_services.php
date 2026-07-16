<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        // Per-PLAN feature bullets (one per line), shown as ticks on the public
        // pricing card. Line-level features already live in serviceData, but
        // those are the same for every plan in a line — repeating them on
        // Starter and Business would imply the tiers are identical. This is the
        // admin's own text, so nothing on the card is invented.
        Database::instance()->statement('ALTER TABLE services ADD COLUMN features TEXT NULL');
    }

    public function down(): void
    {
        try {
            Database::instance()->statement('ALTER TABLE services DROP COLUMN features');
        } catch (\Throwable $e) {
            // older SQLite — ignore
        }
    }
};
