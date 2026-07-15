<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_name', 255, true);
            $table->string('email', 255, true);
            $table->string('phone', 60, true);
            $table->string('abn', 20, true);
            $table->string('address_line1', 255, true);
            $table->string('address_locality', 120, true);
            $table->string('address_region', 60, true);
            $table->string('address_postcode', 12, true);
            $table->string('address_country', 60, true, 'Australia');
            $table->text('notes', true);
            $table->string('status', 20, false, 'active');
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
