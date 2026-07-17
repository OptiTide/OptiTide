<?php

use App\Core\Database;

/**
 * invoices.no_auto_chase — exempt an invoice from the automated dunning cycle.
 *
 * For history ported in from another system. An unpaid 2023 invoice imported today
 * has a due date two years past, so the overdue sweep (RecurringBiller::markOverdue)
 * picks it up on its next run, flips it Overdue, applies a LATE FEE, emails the
 * client "your invoice is overdue", and 30 days later SuspensionService cuts them
 * off. The client gets fined and suspended because you migrated CRM, over a debt you
 * may already be handling by phone.
 *
 * Defaults to 0, so nothing about the normal chase changes: every existing invoice
 * and every new one is still chased exactly as before. This only lets you opt a row
 * out at import time.
 *
 * Raw ALTER per the house convention (Schema has no table()), driver-branched, and
 * column-guarded so it is safely re-runnable.
 */
return new class {
    public function up(): void
    {
        $db = Database::instance();

        if ($this->hasColumn('no_auto_chase')) {
            return;
        }

        // Postgres has a real boolean; SQLite stores 0/1 in an INTEGER.
        $type = $db->driver() === 'pgsql' ? 'BOOLEAN NOT NULL DEFAULT FALSE' : 'INTEGER NOT NULL DEFAULT 0';
        $db->statement("ALTER TABLE invoices ADD COLUMN no_auto_chase {$type}");
    }

    public function down(): void
    {
        // Cosmetic to reverse, and SQLite could not DROP COLUMN before 3.35. A
        // leftover column defaulting to 0/false changes no behaviour.
    }

    private function hasColumn(string $column): bool
    {
        $db = Database::instance();

        if ($db->driver() === 'pgsql') {
            return $db->selectOne(
                'SELECT 1 AS n FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                ['invoices', $column]
            ) !== null;
        }

        foreach ($db->select('PRAGMA table_info(invoices)') as $col) {
            if (($col['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
};
