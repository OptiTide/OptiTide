<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id', 'users', 'set null', true); // actor (null = system/guest)
            $table->string('actor_name', 160, true);                 // denormalised so it survives user deletion
            $table->string('action', 80);                            // e.g. invoice.sent, user.login
            $table->string('subject_type', 60, true);                // e.g. invoice, order, user
            $table->string('subject_id', 40, true);
            $table->text('meta', true);                              // JSON context
            $table->string('ip', 45, true);
            $table->timestamp('created_at');
            $table->index('user_id');
            $table->index('action');
            $table->index('subject_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
