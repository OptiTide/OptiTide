<?php

use App\Core\Blueprint;
use App\Core\Schema;

/**
 * Keyword landing pages — admin-authored, root-level URLs (/web-design-perth).
 *
 * Root-level on purpose: /web-design-perth outranks /pages/web-design-perth and
 * reads better in a search result. The public route is registered LAST and
 * validates the slug against a reserved list, so a landing page can never shadow
 * a real route (/services, /blog, /login…) and quietly break the site.
 *
 * A word on why this is a CMS and not a generator: mass-produced near-identical
 * city pages are what Google calls doorway pages, and they can drag down the whole
 * domain rather than just failing to rank. So every page here is a real, distinct
 * row someone wrote — the schema supports good pages, it doesn't spin them.
 *
 * faqs is JSON and drives FAQPage structured data, which is the single cheapest
 * way onto page one for a long-tail query — it can win the "People also ask" box
 * without outranking anyone.
 */
return new class {
    public function up(): void
    {
        if (Schema::hasTable('landing_pages')) {
            return;
        }

        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 180);
            $table->string('title');                       // H1
            $table->string('meta_title', 180, true);       // <title>, falls back to title
            $table->string('meta_description', 320, true);
            $table->string('keyword', 160, true);          // the primary target phrase
            $table->string('location', 120, true);         // 'Perth' — drives local schema
            // Which service line this sells, so the page can pull REAL prices from
            // the catalogue rather than repeating numbers that later drift.
            $table->string('service_slug', 60, true);
            $table->text('intro', true);                   // lead paragraph under the H1
            $table->text('body', true);                    // main HTML content
            $table->text('faqs', true);                    // JSON [{q,a}] -> FAQPage schema
            $table->string('status', 20, false, 'draft');  // draft | published
            $table->string('published_at', 30, true);
            $table->integer('views', false, 0);
            $table->timestamps();
            $table->unique('slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
