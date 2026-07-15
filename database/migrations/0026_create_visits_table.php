<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);
            $table->string('path', 300);
            $table->string('referrer', 300, true);
            $table->string('user_agent', 300, true);
            $table->string('ip', 45, true);
            $table->timestamps();
            $table->index('visitor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
