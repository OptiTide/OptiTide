<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->integer('position', false, 0);
            $table->timestamps();
            $table->unique('key');
        });

        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id', 'boards');
            $table->string('name');
            $table->integer('position', false, 0);
            $table->timestamps();
            $table->index('board_id');
        });

        Schema::create('board_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id', 'boards');
            $table->foreignId('column_id', 'board_columns');
            $table->foreignId('client_id', 'clients', 'set null', true);
            $table->string('title');
            $table->text('notes', true);
            $table->string('due_date', 30, true);
            $table->integer('position', false, 0);
            $table->timestamps();
            $table->index('board_id');
            $table->index('column_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_cards');
        Schema::dropIfExists('board_columns');
        Schema::dropIfExists('boards');
    }
};
