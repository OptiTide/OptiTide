<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            // title + body are JSON — spatie/laravel-translatable stores
            // {"en": "...", "fr": "..."} and resolves against the app locale.
            $table->json('title');
            $table->json('body')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('show_in_footer')->default(false);
            $table->json('meta')->nullable(); // SEO: meta_title, meta_description
            $table->timestamps();

            $table->index('status');
        });

        // Simple key/value site settings (analytics IDs, etc.).
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('cms_pages');
    }
};
