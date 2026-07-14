<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schemas', function (Blueprint $table) {
            $table->id();
            // Stable identifier referenced by products.onboarding_form_key.
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // JSON schema describing the dynamic fields to render (text, file, color, ...).
            $table->json('schema');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('client_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_schema_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Answers keyed by field name.
            $table->json('data');
            // Uploaded brand assets: logo paths, font files, imagery, hex colors.
            $table->json('brand_assets')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_submissions');
        Schema::dropIfExists('form_schemas');
    }
};
