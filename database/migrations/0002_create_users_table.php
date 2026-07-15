<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password_hash', 255, true);
            $table->string('role', 20, false, 'client');
            $table->foreignId('client_id', 'clients', 'cascade', true);
            $table->string('status', 20, false, 'active');
            $table->timestamp('last_login_at');
            $table->timestamps();
            $table->unique('email');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
