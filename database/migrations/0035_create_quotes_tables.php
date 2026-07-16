<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        // A quote is a priced proposal the client accepts, which then becomes an
        // invoice. It mirrors the invoices table on purpose: money is integer
        // minor units with a sibling currency, and GST is the INCLUSIVE
        // component of the total (never added on top) — so an accepted quote
        // converts to an invoice for exactly the figure the client agreed to.
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->foreignId('client_id', 'clients', 'cascade');
            $table->string('status', 20, false, 'draft');
            $table->string('currency', 3, false, 'AUD');
            $table->string('issue_date', 20, true);

            // Date-only (Y-m-d): a quote expiring today stays acceptable all day.
            $table->string('expires_at', 20, true);

            $table->integer('subtotal_cents', false, 0);
            $table->integer('gst_cents', false, 0);
            $table->integer('total_cents', false, 0);
            $table->integer('discount_cents', false, 0);

            // Snapshot of what the client was told, so the quote still reads
            // correctly after the discount is renamed or removed.
            $table->string('discount_label', 160, true);

            $table->text('notes', true);
            $table->text('terms', true);
            $table->string('public_token', 64);

            $table->timestamp('accepted_at');
            $table->timestamp('declined_at');
            $table->text('decline_reason', true);

            // Set once, when the quote converts. It is the idempotency record:
            // an accepted quote already carrying an invoice never mints another.
            // ON DELETE SET NULL so deleting an invoice can't cascade away the
            // quote that evidences what the client agreed to.
            $table->foreignId('converted_invoice_id', 'invoices', 'set null', true);

            $table->timestamps();
            $table->unique('number');
            $table->unique('public_token');
            $table->index('client_id');
            $table->index('status');
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id', 'quotes', 'cascade');
            $table->foreignId('service_id', 'services', 'set null', true);
            $table->string('description', 500);
            $table->integer('quantity', false, 1);
            $table->integer('unit_price_cents', false, 0);
            $table->integer('line_total_cents', false, 0);
            $table->timestamps();
            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
