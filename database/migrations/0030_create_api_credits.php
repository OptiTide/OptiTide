<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        // Prepaid API-credit balance + a hashed personal API key, per client.
        Database::instance()->statement('ALTER TABLE clients ADD COLUMN api_credit_cents INTEGER NOT NULL DEFAULT 0');
        Database::instance()->statement('ALTER TABLE clients ADD COLUMN api_key_hash VARCHAR(64)');
        Database::instance()->statement('ALTER TABLE clients ADD COLUMN api_key_last4 VARCHAR(8)');
        Database::instance()->statement('ALTER TABLE clients ADD COLUMN api_key_created_at VARCHAR(30)');

        // An invoice can be a credit top-up: when it's paid, this many cents of
        // API credit are granted (once).
        Database::instance()->statement('ALTER TABLE invoices ADD COLUMN api_credit_topup_cents INTEGER NOT NULL DEFAULT 0');

        // Append-only ledger: every top-up, usage charge and adjustment.
        Schema::create('api_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients', 'cascade');
            $table->integer('delta_cents', false, 0);          // + top-up/refund, − usage
            $table->integer('balance_after_cents', false, 0);
            $table->string('type', 20);                        // topup | usage | adjust | refund
            $table->string('description', 255, true);
            $table->string('reference', 120, true);            // e.g. invoice:12 (dedupe key)
            $table->text('meta', true);                        // JSON: tokens, model, etc.
            $table->timestamp('created_at');
            $table->index('client_id');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_credit_transactions');
        foreach ([
            'ALTER TABLE clients DROP COLUMN api_credit_cents',
            'ALTER TABLE clients DROP COLUMN api_key_hash',
            'ALTER TABLE clients DROP COLUMN api_key_last4',
            'ALTER TABLE clients DROP COLUMN api_key_created_at',
            'ALTER TABLE invoices DROP COLUMN api_credit_topup_cents',
        ] as $sql) {
            try {
                Database::instance()->statement($sql);
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
    }
};
