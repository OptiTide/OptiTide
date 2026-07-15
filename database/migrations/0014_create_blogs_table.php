<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt', true);
            $table->text('body', true);
            $table->string('meta_title', 255, true);
            $table->text('meta_description', true);
            $table->string('keywords', 255, true);
            $table->string('category', 100, true);
            $table->string('author', 120, false, 'OptiTide');
            $table->string('cover_image', 255, true);
            $table->string('status', 20, false, 'draft');
            $table->string('published_at', 30, true);
            $table->integer('views', false, 0);
            $table->timestamps();
            $table->unique('slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
