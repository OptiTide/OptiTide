<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id', 'invoices', 'cascade');
            $table->foreignId('service_id', 'services', 'set null', true);
            $table->string('description', 500);
            $table->integer('quantity', false, 1);
            $table->integer('unit_price_cents', false, 0);
            $table->integer('line_total_cents', false, 0);
            $table->timestamps();
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
