<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

/**
 * Blacklist monitoring — the WHMCS-style RBL checks the owner asked for.
 *
 * Client domains and mail IPs get checked against the public DNS blacklists
 * (Spamhaus, SpamCop, SORBS, SURBL). When one gets LISTED, a card is auto-created
 * on the SEO or Hosting board — the owner asked for the listings to live "in the
 * boards", where the work happens, not on yet another standalone screen.
 *
 * Also creates the Hosting board itself: the kanbans were Web Design / SEO /
 * Social only, so hosting incidents had no board to land on. Structure is KEEP
 * data (db:reset preserves boards/board_columns), and the seeder blueprint gains
 * the same entry so a fresh install matches.
 */
return new class {
    public function up(): void
    {
        if (! Schema::hasTable('blacklist_targets')) {
            Schema::create('blacklist_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id', 'clients', 'set null', true);
                $table->string('type', 10);            // domain | ip
                $table->string('value', 180);
                $table->string('label', 160, true);    // shown on the card, defaults to value
                $table->string('board', 20, false, 'hosting'); // seo | hosting — where incidents land
                $table->string('status', 12, false, 'unknown'); // ok | listed | unknown
                $table->text('listed_on', true);       // JSON array of zones currently listing it
                // Set while a listing card is open; only ever written when null, so
                // repeated failing checks never stack duplicate cards (the same
                // idempotency shape as the uptime monitor's incident tickets).
                $table->foreignId('incident_card_id', 'board_cards', 'set null', true);
                $table->string('last_checked_at', 30, true);
                $table->timestamps();
                $table->unique(['type', 'value']);
            });
        }

        // The Hosting board. Guarded so re-runs (and installs whose seeder already
        // made it) do nothing.
        $db = Database::instance();
        $board = $db->selectOne('SELECT id FROM boards WHERE key = ?', ['hosting']);

        if ($board === null) {
            $max = (int) ($db->selectOne('SELECT MAX(position) AS p FROM boards')['p'] ?? 0);
            $now = now();
            $db->affecting(
                'INSERT INTO boards (key, name, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                ['hosting', 'Hosting', $max + 1, $now, $now]
            );
            $boardId = (int) $db->selectOne('SELECT id FROM boards WHERE key = ?', ['hosting'])['id'];

            foreach (['Issues', 'In Progress', 'Monitoring', 'Resolved'] as $i => $name) {
                $db->affecting(
                    'INSERT INTO board_columns (board_id, name, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$boardId, $name, $i, $now, $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_targets');
        // The hosting board stays — it may hold real cards by the time anyone
        // rolls this back, and deleting a board full of work is not a rollback.
    }
};
