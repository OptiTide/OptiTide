<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients', 'cascade');
            $table->foreignId('service_id', 'services', 'set null', true);
            $table->string('label');
            $table->string('billing_type', 20, false, 'one_off');
            $table->string('interval', 20, true);
            $table->integer('price_cents', false, 0);
            $table->string('currency', 3, false, 'AUD');
            $table->string('status', 20, false, 'active');
            $table->string('started_at', 20, true);
            $table->string('next_invoice_date', 20, true);
            $table->string('last_invoiced_at', 20, true);
            $table->text('notes', true);
            $table->timestamps();
            $table->index('client_id');
            $table->index('next_invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_services');
    }
};
