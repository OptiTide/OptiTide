<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            // Which agreement blade template renders this contract's PDF.
            $table->string('template_key')->default('service_agreement');
            $table->string('status')->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Bespoke builds and hosting retainers require a signed agreement
            // before onboarding can be completed.
            $table->boolean('requires_contract')->default(false)->after('onboarding_form_key');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('requires_contract');
        });

        Schema::dropIfExists('contracts');
    }
};
