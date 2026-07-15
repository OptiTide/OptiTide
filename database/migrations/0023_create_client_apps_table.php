<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('client_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients');
            $table->string('name');
            $table->string('url');
            $table->string('environment', 40, true);   // Production | Staging | …
            $table->string('status', 20, false, 'live');
            $table->text('notes', true);
            $table->timestamps();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_apps');
    }
};
