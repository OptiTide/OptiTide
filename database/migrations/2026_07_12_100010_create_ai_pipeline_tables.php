<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // mockup_html (stage 3) or logic_code (stage 6).
            $table->string('type');
            $table->string('status')->default('generating');
            // Claude-generated Tailwind HTML or application code.
            $table->longText('content')->nullable();
            // The context injected into the prompt (colors, logo paths, industry data),
            // kept for reproducibility and QA audits.
            $table->json('prompt_context')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('github_repo_url')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type', 'version']);
        });

        Schema::create('mockup_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_artifact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Pin position as percentages of the rendered iframe viewport.
            $table->decimal('x', 8, 4);
            $table->decimal('y', 8, 4);
            $table->text('comment');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mockup_annotations');
        Schema::dropIfExists('generated_artifacts');
    }
};
