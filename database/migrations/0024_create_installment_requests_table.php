<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('installment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients');
            $table->foreignId('service_id', 'services', 'set null', true);
            $table->foreignId('engagement_id', 'client_services', 'set null', true);
            $table->string('category', 60, true);
            $table->string('plan_key', 40);
            $table->integer('price_cents', false, 0);
            $table->string('status', 20, false, 'pending');   // pending | approved | declined
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_requests');
    }
};
