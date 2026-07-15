<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->foreignId('client_id', 'clients', 'cascade');
            $table->string('status', 20, false, 'draft');
            $table->string('currency', 3, false, 'AUD');
            $table->string('issue_date', 20, true);
            $table->string('due_date', 20, true);
            $table->integer('subtotal_cents', false, 0);
            $table->integer('gst_cents', false, 0);
            $table->integer('total_cents', false, 0);
            $table->integer('amount_paid_cents', false, 0);
            $table->text('notes', true);
            $table->string('payoneer_link', 500, true);
            $table->string('public_token', 64);
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->unique('number');
            $table->unique('public_token');
            $table->index('client_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
