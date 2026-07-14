<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One signature per model. Closes the double-submit race: a concurrent
        // second INSERT fails atomically before any PNG/PDF is written, and the
        // controller's transaction rolls back cleanly.
        Schema::table('signatures', function (Blueprint $table) {
            $table->unique(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropUnique(['model_type', 'model_id']);
        });
    }
};
