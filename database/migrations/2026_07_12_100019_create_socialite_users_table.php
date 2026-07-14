<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a User to one or more OAuth providers (dutchcodingcompany/filament-socialite).
 * Provider identity lives here, NOT as columns on `users`, so a user can connect
 * several providers. Matches the package's SocialiteUser model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialite_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('provider');
            $table->string('provider_id');
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialite_users');
    }
};
