<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft');
            // When a scheduled article should go live; the daily cron publishes it.
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // SEO metadata: meta title/description, focus keywords, OG image.
            $table->json('meta')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            // The client whose social channels this post is for; null = agency's own channels.
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform');
            $table->text('content');
            $table->text('image_prompt')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('pending_review');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            // Platform-side post ID after successful distribution.
            $table->string('external_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('blogs');
    }
};
