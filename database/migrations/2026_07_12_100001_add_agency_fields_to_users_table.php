<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->after('password');
            $table->string('company_name')->nullable()->after('role');
            $table->string('phone')->nullable()->after('company_name');
            $table->string('locale', 12)->default('en')->after('phone');
            $table->char('preferred_currency', 3)->default('AUD')->after('locale');
            $table->string('referral_code')->nullable()->unique()->after('preferred_currency');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->timestamp('onboarded_at')->nullable()->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn([
                'role', 'company_name', 'phone', 'locale',
                'preferred_currency', 'referral_code', 'onboarded_at',
            ]);
        });
    }
};
