<?php

use App\Core\Database;

/**
 * Remove three plans that were live on the public site but aren't in the real
 * catalogue: "SEO Retainer" ($990), "Managed Hosting — Basic" ($49) and
 * "Managed Hosting — Pro" ($99). Confirmed as leftovers by the owner.
 *
 * DELETE ONLY IF UNREFERENCED. The owner asked for a delete, but a plan that was
 * ever actually sold cannot be deleted safely:
 *   - discounts.service_id is ON DELETE CASCADE, so deleting a plan would
 *     silently destroy any discount scoped to it;
 *   - invoice_items.service_id is ON DELETE SET NULL, so deleting would strip the
 *     product link off historical TAX INVOICES;
 *   - client_services.service_id likewise, orphaning a live engagement from the
 *     product it bills for.
 * So each plan is checked first: unreferenced ones are deleted, and any that was
 * used is deactivated instead (active = 0) — off the site and unorderable, with
 * its billing history intact. Either way it stops being advertised, which is what
 * was actually asked for.
 */
return new class {
    private const LEFTOVERS = [
        'SEO Retainer',
        'Managed Hosting — Basic',
        'Managed Hosting — Pro',
    ];

    /** table => column referencing services.id */
    private const REFERENCES = [
        'client_services'      => 'service_id',
        'invoice_items'        => 'service_id',
        'project_intakes'      => 'service_id',
        'installment_requests' => 'service_id',
        'discounts'            => 'service_id',
        'quote_items'          => 'service_id',
    ];

    public function up(): void
    {
        $db = Database::instance();

        foreach (self::LEFTOVERS as $name) {
            $row = $db->selectOne('SELECT id FROM services WHERE name = ?', [$name]);
            if (! $row) {
                continue;
            }
            $id = $row['id'];

            $used = [];
            foreach (self::REFERENCES as $table => $column) {
                // A table may not exist yet on an older database; a missing table
                // is simply "no references from it".
                try {
                    $hit = $db->selectOne("SELECT 1 AS n FROM {$table} WHERE {$column} = ? LIMIT 1", [$id]);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($hit) {
                    $used[] = $table;
                }
            }

            if ($used === []) {
                $db->affecting('DELETE FROM services WHERE id = ?', [$id]);
                logger('Removed leftover plan.', ['plan' => $name]);

                continue;
            }

            // Sold at some point — keep the records, just take it off sale.
            $db->affecting('UPDATE services SET active = 0 WHERE id = ?', [$id]);
            logger('Leftover plan was in use — deactivated instead of deleted.', [
                'plan' => $name, 'referenced_by' => implode(', ', $used),
            ]);
        }
    }

    public function down(): void
    {
        // No down: these plans were never part of the catalogue, and recreating
        // them would put them back on a public page at prices that were wrong.
    }
};
