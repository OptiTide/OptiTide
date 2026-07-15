<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        Schema::create('backlinks', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');                       // e.g. "Google Business Profile"
            $table->string('site_url', 500, true);             // homepage / directory URL
            $table->string('submit_url', 500, true);           // where to submit/claim a listing
            $table->string('type', 20, false, 'directory');    // directory | citation | social | guest_post | partner | other
            $table->string('status', 20, false, 'prospect');   // prospect | submitted | live | rejected
            $table->string('anchor_text', 200, true);
            $table->string('link_url', 500, true);             // the page on OUR site the link points to
            $table->integer('domain_authority', true);         // optional DA/DR estimate
            $table->text('notes', true);
            $table->string('last_checked', 30, true);
            $table->timestamps();
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlinks');
    }
};
