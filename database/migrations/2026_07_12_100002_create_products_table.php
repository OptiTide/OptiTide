<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description')->nullable();
            // All monetary values are integer minor units (cents).
            $table->unsignedBigInteger('price');
            $table->char('currency', 3)->default('AUD');
            // Null for one-time purchases; 'month' for recurring subscriptions.
            $table->string('billing_interval')->nullable();
            $table->json('features')->nullable();
            // Which dynamic intake form this product triggers after purchase.
            $table->string('onboarding_form_key')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
