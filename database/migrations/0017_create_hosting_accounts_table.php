<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients', 'set null', true);
            $table->string('domain');
            $table->string('username', 64);
            $table->string('plan', 120, true);
            $table->string('status', 30, false, 'active');
            $table->string('ip_address', 45, true);
            $table->integer('disk_used_mb', true);
            $table->integer('disk_limit_mb', true);
            $table->string('server', 120, true);
            $table->string('synced_at', 30, true);
            $table->timestamps();
            $table->unique('username');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_accounts');
    }
};
