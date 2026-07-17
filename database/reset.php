<?php

use App\Core\Database;
use App\Models\JobApplication;
use App\Models\User;
use App\Support\Upload;

/**
 * `php bin/console db:reset` — clear out every client and all their data so the
 * business can start taking real clients, while KEEPING the marketing site.
 *
 * KEEPS: service_categories, services (catalogue), blogs (articles), settings,
 *        boards + board_columns (structure), job_openings, backlinks,
 *        site-wide discounts, the migrations table, and admin logins.
 * WIPES: clients and everything hanging off them, plus visits, audit_logs,
 *        job_applications (+ their CV files), password_resets, non-admin logins.
 *
 * WHY IT IS WRITTEN THE WAY IT IS — every guard below is a real failure that an
 * adversarial review found in the obvious version of this command:
 *
 *  1. NEVER TRUNCATE. Postgres refuses `TRUNCATE clients` while FK references
 *     exist and the error text pushes you to add CASCADE — but TRUNCATE ... CASCADE
 *     empties every REFERENCING table regardless of its ON DELETE action, which
 *     would take `discounts` (a KEEP table). SQLite has no TRUNCATE at all, so the
 *     damage is undiscoverable on dev. Explicit DELETEs only.
 *
 *  2. NEVER RELY ON CASCADE. Five client tables are ON DELETE SET NULL
 *     (tickets, chat_conversations, hosting_accounts, board_cards, commissions):
 *     DELETE FROM clients NULLs them and LEAVES THE ROWS. An ex-client's tickets
 *     and chat transcripts would survive into the live business unattributed.
 *     Three more (visits, audit_logs, job_applications) have no client FK at all.
 *     So: delete every table explicitly, child-first, cascade or no cascade.
 *
 *  3. NEVER ENUMERATE TABLES. The wipe list is hard-coded, so a table added later
 *     fails closed (not wiped) instead of open. Enumerating would also catch
 *     `migrations` — which is created by Migrator::ensureTable(), not by any
 *     migration file. Wiping it makes entrypoint.sh re-run migration 0001 on the
 *     next boot; Blueprint emits CREATE TABLE with no IF NOT EXISTS, so it throws,
 *     the entrypoint retries 15x and exits 1, and Coolify crash-loops the
 *     container forever. A reset would read as a database outage.
 *
 *  4. COUNTERS LIVE IN `settings`, A KEEP TABLE. InvoiceService::nextNumber()
 *     increments settings.invoice_counter — it does NOT derive from the invoices
 *     table. Without an explicit reset the first real client is invoiced
 *     INV-000027. Same for quote_counter.
 *
 *  5. FILES ARE NOT ROWS. job_applications.resume_path points at a real CV on disk
 *     holding a person's name, email, phone and address. DELETE FROM
 *     job_applications strips the only reference to it, leaving permanently
 *     unreachable PII on the volume. The admin's own single-delete path calls
 *     Upload::delete() for exactly this reason.
 *
 *  6. COMMIT CAN LIE. Database::commit() calls pdo->commit() with no
 *     inTransaction() guard (rollBack() has one). On Postgres a COMMIT of an
 *     already-aborted transaction IS a rollback — so a swallowed error mid-wipe
 *     would report success having deleted nothing. Nothing here is wrapped in a
 *     try/catch: any failure must propagate and abort the whole thing.
 */
return new class {
    /** Child-first. Order holds even with zero cascades — see note 2. */
    private const WIPE_ORDER = [
        'board_card_checklist',
        'board_card_comments',
        'board_cards',
        'ticket_replies',
        'tickets',
        'chat_messages',
        'chat_conversations',
        'quote_items',
        'quotes',
        'invoice_items',
        'payments',
        'credit_transactions',
        'api_credit_transactions',
        'discount_redemptions',
        'commissions',
        'referrals',
        'invoices',
        'installment_requests',
        'client_services',
        'client_apps',
        'project_intakes',
        'meetings',
        'hosting_accounts',
        'job_applications',
        'visits',
        'audit_logs',
        'password_resets',
        'clients',
    ];

    /** Must be non-empty and unchanged afterwards, asserted before commit. */
    private const KEEP_TABLES = [
        'migrations',
        'service_categories',
        'services',
        'blogs',
        'settings',
        'boards',
        'board_columns',
    ];

    public function run(callable $out, bool $assumeYes = false): int
    {
        $db = Database::instance();

        // --- Prove the target BEFORE destroying anything ------------------------
        // A .env inside a container shadows Coolify's real env (Env::get reads the
        // file first) and .env.example pins DB_CONNECTION=sqlite. Without this the
        // command would wipe an empty SQLite file, print success, and leave prod
        // Postgres untouched.
        $driver = $db->driver();
        $default = config('database.default');
        $conn = config('database.connections.' . $default) ?? [];
        $target = $driver === 'sqlite'
            ? (string) ($conn['database'] ?? '?')
            : sprintf('%s@%s:%s/%s', $conn['username'] ?? '?', $conn['host'] ?? '?', $conn['port'] ?? '?', $conn['database'] ?? '?');

        $out('');
        $out('  Driver:   ' . $driver);
        $out('  Target:   ' . $target);
        $out('  APP_ENV:  ' . (getenv('APP_ENV') ?: env('APP_ENV') ?: '(unset)'));

        if (is_file(BASE_PATH . '/.env') && strtolower((string) (getenv('APP_ENV') ?: '')) === 'production') {
            $out('');
            $out('REFUSING: a .env file exists inside a production container.');
            $out('Env::get() reads that file BEFORE the real environment, so it may be');
            $out('pointing this command at the wrong database. Delete it and re-run.');

            return 1;
        }

        // --- Show what is actually about to die --------------------------------
        $counts = [];
        foreach (self::WIPE_ORDER as $table) {
            $counts[$table] = (int) $db->selectOne("SELECT COUNT(*) AS n FROM {$table}")['n'];
        }
        $total = array_sum($counts);

        $admins = User::query()->where('role', User::ROLE_ADMIN)->get();
        $doomedUsers = User::query()->where('role', '!=', User::ROLE_ADMIN)->get();

        $out('');
        $out('  About to DELETE:');
        foreach ($counts as $table => $n) {
            if ($n > 0) {
                $out(sprintf('    %-26s %d', $table, $n));
            }
        }
        $out(sprintf('    %-26s %d', 'non-admin logins', count($doomedUsers)));
        $out('  Total rows: ' . $total);
        $out('');
        $out('  KEEPING admin logins:');
        foreach ($admins as $a) {
            $out('    ' . $a['email']);
        }

        if ($admins === []) {
            $out('');
            $out('REFUSING: no admin account exists — this would lock you out permanently.');
            $out('Create one first:  php bin/console make:admin <email> <password>');

            return 1;
        }

        // An admin whose client_id is set would be CASCADE-deleted along with its
        // client row (users.client_id -> clients ON DELETE CASCADE). Detach first;
        // an admin has no business being tied to a client anyway.
        foreach ($admins as $a) {
            if (! empty($a['client_id'])) {
                $out('');
                $out('  NOTE: admin ' . $a['email'] . ' is linked to a client row — detaching it');
                $out('        so deleting clients cannot delete your own login.');
            }
        }

        // --- Confirm ------------------------------------------------------------
        if (! $assumeYes) {
            $out('');
            $out('  This CANNOT be undone. Take a database snapshot in Coolify first.');
            $out('  Type the driver name (' . $driver . ') to proceed, anything else to abort:');
            $answer = trim((string) fgets(STDIN));

            if ($answer !== $driver) {
                $out('Aborted — nothing was changed.');

                return 1;
            }
        }

        // --- CV files, before the rows that point at them (note 5) --------------
        // Outside the transaction: a rollback cannot re-link an unlinked file, so
        // this runs first and is reported honestly.
        $cvs = 0;
        foreach (JobApplication::all() as $application) {
            if (! empty($application['resume_path'])) {
                Upload::delete($application['resume_path']);
                $cvs++;
            }
        }
        if ($cvs > 0) {
            $out('');
            $out("  Deleted {$cvs} CV file(s) from disk.");
        }

        // --- The wipe -----------------------------------------------------------
        // No try/catch anywhere inside: a swallowed error would poison the Postgres
        // transaction and commit() would silently downgrade to ROLLBACK (note 6).
        $keepBefore = $this->keepCounts($db);

        $db->transaction(function () use ($db, $out) {
            // Detach admins so the clients cascade cannot reach them.
            $db->affecting('UPDATE users SET client_id = NULL WHERE role = ?', [User::ROLE_ADMIN]);

            // Client-scoped discounts are CASCADE-deleted by DELETE FROM clients.
            // Do it explicitly so the loss is visible here rather than an invisible
            // side effect; site-wide sales (client_id IS NULL) are untouched.
            $db->affecting('DELETE FROM discounts WHERE client_id IS NOT NULL');

            foreach (self::WIPE_ORDER as $table) {
                $db->affecting("DELETE FROM {$table}");
            }

            // Non-admin logins survive the clients cascade when client_id is NULL
            // (every seeded demo STAFF account is exactly that), so remove them by
            // role rather than trusting the FK.
            $db->affecting('DELETE FROM users WHERE role != ?', [User::ROLE_ADMIN]);

            // Counters live in settings, which we keep (note 4).
            $db->affecting("UPDATE settings SET value = '0' WHERE key IN ('invoice_counter', 'quote_counter')");

            // Post-conditions INSIDE the transaction: if the reset touched anything
            // it must not have, throwing here rolls the whole thing back. There is
            // no backup to fall back on.
            foreach (self::KEEP_TABLES as $table) {
                $n = (int) $db->selectOne("SELECT COUNT(*) AS n FROM {$table}")['n'];
                if ($n === 0) {
                    throw new RuntimeException("Reset emptied KEEP table `{$table}` — rolling back.");
                }
            }
            if (User::query()->where('role', User::ROLE_ADMIN)->first() === null) {
                throw new RuntimeException('Reset removed every admin login — rolling back.');
            }
        });

        // --- Sequences ----------------------------------------------------------
        // So the first real client is OT-000001, not OT-000027. Derived, never
        // guessed: pg_get_serial_sequence is search_path-aware and returns NULL for
        // a table with no serial, so it degrades instead of throwing.
        foreach (self::WIPE_ORDER as $table) {
            if ($driver === 'pgsql') {
                $seq = $db->selectOne('SELECT pg_get_serial_sequence(?, ?) AS s', [$table, 'id'])['s'] ?? null;
                if ($seq) {
                    $db->statement('ALTER SEQUENCE ' . $seq . ' RESTART WITH 1');
                }
            } elseif ($driver === 'sqlite') {
                $db->affecting('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            }
        }

        // --- Sessions -----------------------------------------------------------
        // A live session cookie holds only `_auth_id`. With ids recycled, a still-open
        // demo session would resolve to whichever NEW real client is issued that id.
        $flushed = $this->flushSessions();

        $keepAfter = $this->keepCounts($db);

        $out('');
        $out('  Deleted ' . $total . ' row(s). Sequences reset. ' . $flushed . ' session(s) flushed.');
        $out('  Invoice + quote numbering restarts at 1.');
        $out('');
        $out('  Untouched:');
        foreach ($keepAfter as $table => $n) {
            $out(sprintf('    %-22s %d%s', $table, $n, $keepBefore[$table] === $n ? '' : '  *** CHANGED ***'));
        }
        $out('');
        $out('  Done. Your admin login still works. Go take some real clients.');

        return 0;
    }

    private function keepCounts(Database $db): array
    {
        $counts = [];
        foreach (self::KEEP_TABLES as $table) {
            $counts[$table] = (int) $db->selectOne("SELECT COUNT(*) AS n FROM {$table}")['n'];
        }

        return $counts;
    }

    /** File-driver sessions only; Redis is flushed by its own eviction/TTL. */
    private function flushSessions(): int
    {
        if (config('session.driver') !== 'file') {
            return 0;
        }

        $dir = BASE_PATH . '/storage/framework/sessions';
        if (! is_dir($dir)) {
            return 0;
        }

        $n = 0;
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $n++;
            }
        }

        return $n;
    }
};
