<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description', true);
            $table->integer('sort_order', false, 0);
            $table->timestamps();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_categories');
    }
};
