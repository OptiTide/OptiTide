<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url'); // the https endpoint to poll
            $table->string('status')->default('unknown'); // up | down | unknown
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            // The open incident tickets, so repeated failing checks don't spawn
            // duplicates — nulled again when the condition clears.
            $table->foreignId('incident_ticket_id')->nullable()->constrained('helpdesk_tickets')->nullOnDelete();
            $table->foreignId('ssl_ticket_id')->nullable()->constrained('helpdesk_tickets')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_monitors');
    }
};
