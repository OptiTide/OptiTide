<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        // Late fee is stored separately (GST-free penalty) and folded into
        // total_cents so balance = total − paid stays correct everywhere.
        Database::instance()->statement('ALTER TABLE invoices ADD COLUMN late_fee_cents INTEGER NOT NULL DEFAULT 0');
        // none | requested (staff asked to waive) | waived (admin approved)
        Database::instance()->statement("ALTER TABLE invoices ADD COLUMN late_fee_waiver_status VARCHAR(20) NOT NULL DEFAULT 'none'");
    }

    public function down(): void
    {
        foreach (['late_fee_cents', 'late_fee_waiver_status'] as $column) {
            try {
                Database::instance()->statement("ALTER TABLE invoices DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
    }
};
