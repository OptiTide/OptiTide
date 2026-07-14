<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * At most one OPEN conversation per client — a partial unique index enforces it
 * at the DB level so concurrent "open a chat" requests can't create duplicates.
 * Partial indexes exist on SQLite (dev/test) and PostgreSQL (prod); MySQL lacks
 * them, so there the app-level guard in ChatService::openConversationFor stands
 * alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->supportsPartialIndex()) {
            DB::statement(
                "CREATE UNIQUE INDEX chat_conversations_one_open_per_user ".
                "ON chat_conversations (user_id) WHERE status = 'open'"
            );
        }
    }

    public function down(): void
    {
        if ($this->supportsPartialIndex()) {
            DB::statement('DROP INDEX IF EXISTS chat_conversations_one_open_per_user');
        }
    }

    protected function supportsPartialIndex(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['sqlite', 'pgsql'], true);
    }
};
