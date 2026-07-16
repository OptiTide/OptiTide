<?php
/**
 * The "Items total / Discount" rows that sit above the subtotal on a quote.
 * Shared by the client, admin and public quote views so the three can never
 * drift. (The PDF has its own markup — dompdf takes limited CSS — same shape.)
 *
 * Expects: $quote.
 *
 * The discount is shown against the FULL items total, and GST below is struck on
 * the discounted amount — a true statement of the price and the GST within it.
 */
$discountCents = (int) ($quote['discount_cents'] ?? 0);
if ($discountCents <= 0) {
    return;
}
$grossCents = (int) $quote['subtotal_cents'] + (int) $quote['gst_cents'] + $discountCents;
?>
<tr><td class="text-muted">Items total</td><td class="text-end money"><?= e(money($grossCents, $quote['currency'])->format()) ?></td></tr>
<tr>
    <td class="text-success"><i class="bi bi-tag-fill"></i> <?= e($quote['discount_label'] ?: 'Discount') ?></td>
    <td class="text-end money text-success">− <?= e(money($discountCents, $quote['currency'])->format()) ?></td>
</tr>
