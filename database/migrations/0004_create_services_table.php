<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id', 'service_categories', 'set null', true);
            $table->string('name');
            $table->text('description', true);
            $table->string('billing_type', 20, false, 'one_off');
            $table->string('interval', 20, true);
            $table->integer('price_cents', false, 0);
            $table->string('currency', 3, false, 'AUD');
            $table->boolean('active', false, true);
            $table->timestamps();
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
