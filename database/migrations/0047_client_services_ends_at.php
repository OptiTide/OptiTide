<?php

use App\Core\Database;

/**
 * client_services.ends_at — when an engagement finished (or is scheduled to).
 *
 * started_at existed but was never editable (EngagementController::engagementData
 * has no such field, so the date was stamped once at creation and frozen). An END
 * date did not exist at all: cancelling only flipped status to 'cancelled', so the
 * system could never say WHEN a service stopped. Both matter for porting — a client
 * brought over from another system has engagements that began in 2023 and may have
 * ended in 2024, and neither was representable.
 *
 * Nullable, so every existing row means "open-ended", which is exactly what they
 * were. RecurringBiller::dueForInvoicing now excludes rows whose ends_at has passed
 * — without that, setting an end date would be cosmetic and the client would keep
 * being billed for a service that had finished.
 *
 * Raw ALTER per the house convention (Schema has no table()), driver-branched and
 * column-guarded so it is re-runnable.
 */
return new class {
    public function up(): void
    {
        $db = Database::instance();

        if ($this->hasColumn('ends_at')) {
            return;
        }

        // Matches started_at's shape: a VARCHAR date string, not a native DATE —
        // the sibling columns in this table are all VARCHAR(20) and the biller
        // compares them as strings.
        $db->statement('ALTER TABLE client_services ADD COLUMN ends_at VARCHAR(20) NULL');
    }

    public function down(): void
    {
        // Cosmetic to reverse, and SQLite could not DROP COLUMN before 3.35. A
        // leftover nullable column means "open-ended" and changes no behaviour.
    }

    private function hasColumn(string $column): bool
    {
        $db = Database::instance();

        if ($db->driver() === 'pgsql') {
            return $db->selectOne(
                'SELECT 1 AS n FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                ['client_services', $column]
            ) !== null;
        }

        foreach ($db->select('PRAGMA table_info(client_services)') as $col) {
            if (($col['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
};
