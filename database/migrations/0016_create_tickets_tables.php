<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->foreignId('client_id', 'clients', 'set null', true);
            $table->foreignId('user_id', 'users', 'set null', true);
            $table->string('subject');
            $table->string('category', 60, true);
            $table->string('status', 20, false, 'open');
            $table->string('priority', 20, false, 'normal');
            $table->string('last_reply_at', 30, true);
            $table->timestamps();
            $table->unique('number');
            $table->index('client_id');
            $table->index('status');
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id', 'tickets');
            $table->foreignId('user_id', 'users', 'set null', true);
            $table->text('body');
            $table->boolean('is_staff', false, false);
            $table->boolean('is_internal', false, false);
            $table->timestamps();
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
    }
};
