<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('project_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients');
            $table->foreignId('service_id', 'services', 'set null', true);
            $table->string('reference', 20, true);
            $table->string('category', 60, true);
            $table->text('data');
            $table->timestamps();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_intakes');
    }
};
