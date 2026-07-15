<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value', true);
            $table->timestamps();
            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
