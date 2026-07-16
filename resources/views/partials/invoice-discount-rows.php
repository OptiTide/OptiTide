<?php
/**
 * The "Items total / Discount" rows that sit above the subtotal on an invoice.
 * Shared by the client, admin and public pay views so the three can never drift.
 * (The PDF has its own markup — TCPDF takes limited HTML — but the same shape.)
 *
 * Expects: $invoice.
 *
 * The discount is shown against the FULL items total, and GST below is struck on
 * the discounted amount — a true record of what was charged and what GST was
 * actually collected.
 */
$discountCents = (int) ($invoice['discount_cents'] ?? 0);
if ($discountCents <= 0) {
    return;
}
$grossCents = (int) $invoice['subtotal_cents'] + (int) $invoice['gst_cents'] + $discountCents;
?>
<tr><td class="text-muted">Items total</td><td class="text-end money"><?= e(money($grossCents, $invoice['currency'])->format()) ?></td></tr>
<tr>
    <td class="text-success"><i class="bi bi-tag-fill"></i> <?= e($invoice['discount_label'] ?: 'Discount') ?></td>
    <td class="text-end money text-success">− <?= e(money($discountCents, $invoice['currency'])->format()) ?></td>
</tr>
