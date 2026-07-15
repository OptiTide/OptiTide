<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id', 'clients');
            $table->foreignId('created_by', 'users', 'set null', true);
            $table->string('title');
            $table->text('description', true);
            $table->string('meeting_at', 30);          // 'Y-m-d H:i:s'
            $table->string('location', 300, true);      // Zoom / Meet / phone link or place
            $table->string('status', 20, false, 'scheduled'); // scheduled | completed | cancelled
            $table->timestamps();
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
