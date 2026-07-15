<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients', 'set null', true);
            $table->string('token', 64);
            $table->string('name', 120, true);
            $table->string('email', 180, true);
            $table->string('status', 20, false, 'open');   // open | closed
            $table->string('mode', 20, false, 'ai');        // ai | human (staff took over)
            $table->string('last_message_at', 30, true);
            $table->timestamps();
            $table->unique('token');
            $table->index('status');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id', 'chat_conversations');
            $table->string('sender', 20);                    // visitor | agent
            $table->boolean('is_ai', false, false);          // agent message written by AI?
            $table->foreignId('user_id', 'users', 'set null', true);
            $table->text('body');
            $table->timestamps();
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
