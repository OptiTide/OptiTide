<?php

use App\Core\Database;

return new class {
    /**
     * Billing for client apps.
     *
     * DESIGN: an app is NOT a second billing engine. RecurringBiller invoices
     * client_services rows and nothing else, so an app is one of three things:
     *
     *   none      — hosted as part of something else. No money, shows nothing.
     *   recurring — engagement_id points at the client_services row that the
     *               RecurringBiller ALREADY invoices. The engagement owns the
     *               price/interval/schedule; the app stores no money of its own.
     *   one_off   — price_cents holds an agreed one-off charge, raised manually
     *               on an invoice exactly like a one_off engagement (which the
     *               biller also skips: it filters billing_type = recurring).
     *
     * RecurringBiller is deliberately NOT taught to bill apps. If it were, an
     * app carrying its own recurring price AND an engagement_id would be
     * invoiced twice for one thing — once off client_services, once off
     * client_apps — with no shared key to reconcile them. Linking keeps a
     * single source of truth and a single invoice.
     *
     * "interval" is quoted: it is a PostgreSQL keyword, and Blueprint quotes
     * every identifier, so the existing client_services.interval column was
     * created as "interval" too.
     */
    public function up(): void
    {
        Database::instance()->statement('ALTER TABLE client_apps ADD COLUMN price_cents INTEGER NULL');
        Database::instance()->statement("ALTER TABLE client_apps ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'AUD'");
        // none | one_off | recurring
        Database::instance()->statement("ALTER TABLE client_apps ADD COLUMN billing_type VARCHAR(20) NOT NULL DEFAULT 'none'");
        // monthly | quarterly | yearly — reserved. Under the link-only model the
        // linked engagement owns the interval, so this stays NULL.
        Database::instance()->statement('ALTER TABLE client_apps ADD COLUMN "interval" VARCHAR(20) NULL');
        Database::instance()->statement('ALTER TABLE client_apps ADD COLUMN engagement_id INTEGER NULL');
    }

    public function down(): void
    {
        foreach (['price_cents', 'currency', 'billing_type', '"interval"', 'engagement_id'] as $column) {
            try {
                Database::instance()->statement("ALTER TABLE client_apps DROP COLUMN $column");
            } catch (\Throwable $e) {
                // older SQLite — ignore
            }
        }
    }
};
