<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Database::instance()->statement('ALTER TABLE clients ADD COLUMN credit_cents INTEGER NOT NULL DEFAULT 0');

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients');
            $table->integer('amount_cents', false, 0);   // + adds credit, − applies/removes
            $table->string('type', 20);                  // add | applied | adjust
            $table->string('reason', 255, true);
            $table->foreignId('invoice_id', 'invoices', 'set null', true);
            $table->foreignId('recorded_by', 'users', 'set null', true);
            $table->timestamps();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        try {
            Database::instance()->statement('ALTER TABLE clients DROP COLUMN credit_cents');
        } catch (\Throwable $e) {
            // older SQLite — ignore
        }
    }
};
