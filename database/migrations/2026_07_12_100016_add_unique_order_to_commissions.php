<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * At most one commission per order (belt-and-suspenders alongside the
 * converted_at compare-and-swap). order_id is nullable, and both SQLite and
 * PostgreSQL permit multiple NULLs under a unique index, so manual/no-order
 * commissions are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });
    }
};
