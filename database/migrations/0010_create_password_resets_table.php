<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at', false, 'CURRENT_TIMESTAMP');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
