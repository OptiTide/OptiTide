<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::instance();
        $db->statement('ALTER TABLE client_services ADD COLUMN reference VARCHAR(20)');

        // Backfill job references for existing engagements.
        foreach ($db->select('SELECT id FROM client_services') as $row) {
            $ref = 'JOB-' . str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT);
            $db->affecting('UPDATE client_services SET reference = ? WHERE id = ?', [$ref, $row['id']]);
        }
    }

    public function down(): void
    {
        try {
            Database::instance()->statement('ALTER TABLE client_services DROP COLUMN reference');
        } catch (\Throwable $e) {
            // older SQLite — ignore
        }
    }
};
