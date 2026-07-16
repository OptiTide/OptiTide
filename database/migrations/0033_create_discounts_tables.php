<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        // One table serves all three shapes:
        //  - a code a client types at checkout        -> code set
        //  - an automatic site-wide sale              -> code NULL, is_sale = 1
        //  - a one-off deal for a single client       -> client_id set
        // A staff-applied ad-hoc discount needs no row at all: it writes
        // discount_cents straight onto the invoice.
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            // NULL = no code needed (an automatic sale). Uniqueness is enforced
            // on the normalised upper-case value in DiscountService.
            $table->string('code', 40, true);
            $table->string('name', 120);
            $table->string('type', 20, false, 'percent');

            // percent -> basis points (2000 = 20%), matching the house
            // convention used by GST and affiliate commission.
            // fixed   -> integer minor units (cents), like all other money here.
            $table->integer('value', false, 0);
            $table->string('currency', 3, false, 'AUD');

            // What it applies to. 'all' | 'category' | 'service'
            $table->string('scope', 20, false, 'all');
            $table->foreignId('category_id', 'service_categories', 'cascade', true);
            $table->foreignId('service_id', 'services', 'cascade', true);

            $table->integer('min_spend_cents', true);
            $table->string('starts_at', 20, true);
            $table->string('ends_at', 20, true);
            $table->integer('max_uses', true);
            $table->integer('max_uses_per_client', true);
            $table->integer('uses', false, 0);
            $table->foreignId('client_id', 'clients', 'cascade', true);

            // A sale auto-applies with no code AND advertises itself on the
            // public catalogue with struck-through pricing.
            $table->boolean('is_sale', false, false);
            $table->boolean('active', false, true);
            $table->timestamps();

            $table->index('code');
            $table->index('active');
        });

        // Every application is recorded: enforces usage limits, and gives a real
        // audit trail of what was given away and to whom.
        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id', 'discounts', 'cascade');
            $table->foreignId('client_id', 'clients', 'cascade', true);
            $table->foreignId('invoice_id', 'invoices', 'cascade', true);
            $table->integer('amount_cents', false, 0);
            $table->string('currency', 3, false, 'AUD');
            $table->timestamps();

            $table->index('discount_id');
            $table->index('client_id');
        });

        // GST is INCLUSIVE here, so a discount must reduce the inclusive total
        // and let GST be re-derived from the DISCOUNTED figure — otherwise we'd
        // remit GST on money we never collected. See InvoiceService.
        // Raw ALTERs to match the house convention (Schema has no table()).
        Database::instance()->statement('ALTER TABLE invoices ADD COLUMN discount_cents INTEGER NOT NULL DEFAULT 0');
        Database::instance()->statement('ALTER TABLE invoices ADD COLUMN discount_id INTEGER NULL');
        // Snapshot of what the client was told, so the invoice still reads
        // correctly after the discount is renamed or deleted.
        Database::instance()->statement('ALTER TABLE invoices ADD COLUMN discount_label VARCHAR(160) NULL');
    }

    public function down(): void
    {
        foreach (['discount_cents', 'discount_id', 'discount_label'] as $column) {
            try {
                Database::instance()->statement("ALTER TABLE invoices DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discounts');
    }
};
