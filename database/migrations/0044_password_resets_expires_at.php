<?php

use App\Core\Database;

/**
 * Give password_resets an explicit expiry.
 *
 * PasswordResetController hard-codes 60 minutes (`strtotime($record['created_at'])
 * < time() - 3600`). That is right for "I forgot my password" — the person is at
 * their keyboard, waiting. It is useless for a CLIENT INVITE: the agency adds a
 * client during the day and the client opens their email that evening, by which
 * time the link is dead and their first experience of the portal is an error.
 *
 * So: a nullable expires_at. When set it wins; when NULL the existing 60-minute
 * rule still applies, so every current reset link keeps behaving exactly as it did
 * and no existing row needs backfilling.
 *
 * Raw ALTER because Schema has no table() — the house convention (see 0033:71).
 * Column types differ per driver, so branch: prod is Postgres, dev is SQLite.
 */
return new class {
    public function up(): void
    {
        $db = Database::instance();

        if ($this->hasColumn('expires_at')) {
            return;
        }

        $type = $db->driver() === 'pgsql' ? 'TIMESTAMP' : 'DATETIME';
        $db->statement("ALTER TABLE password_resets ADD COLUMN expires_at {$type} NULL");
    }

    public function down(): void
    {
        // SQLite could not DROP COLUMN before 3.35 and this is cosmetic either way:
        // a leftover nullable column changes no behaviour, since NULL means "use the
        // original 60-minute rule".
    }

    private function hasColumn(string $column): bool
    {
        $db = Database::instance();

        if ($db->driver() === 'pgsql') {
            return $db->selectOne(
                'SELECT 1 AS n FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                ['password_resets', $column]
            ) !== null;
        }

        foreach ($db->select('PRAGMA table_info(password_resets)') as $col) {
            if (($col['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
};
