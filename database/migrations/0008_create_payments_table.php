<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id', 'invoices', 'cascade');
            $table->foreignId('client_id', 'clients', 'cascade');
            $table->integer('amount_cents', false, 0);
            $table->string('currency', 3, false, 'AUD');
            $table->string('method', 20, false, 'manual');
            $table->string('reference', 255, true);
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by', 'users', 'set null', true);
            $table->text('notes', true);
            $table->timestamps();
            $table->index('invoice_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
