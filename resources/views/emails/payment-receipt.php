<?php
$this->extends('emails.layout');
$amount = money((int) $payment['amount_cents'], $payment['currency'] ?? 'AUD');
$balance = \App\Models\Invoice::balance($invoice);
$method = \App\Models\Payment::METHODS[$payment['method']] ?? ucfirst((string) $payment['method']);
?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: ($client['business_name'] ?? 'there')) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">Thank you — we've received your payment. Here's your receipt.</p>

<table role="presentation" width="100%" style="margin:8px 0 18px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;width:150px;">Invoice</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($invoice['number']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Amount paid</td><td style="padding:6px 0;text-align:right;font-weight:700;color:#0D1530;"><?= e($amount->format()) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Method</td><td style="padding:6px 0;text-align:right;"><?= e($method) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Date</td><td style="padding:6px 0;text-align:right;"><?= e(substr((string) $payment['paid_at'], 0, 10)) ?></td></tr>
    <tr><td style="padding:10px 0 0;font-weight:700;border-top:1px solid #e2e8f0;">Balance remaining</td><td style="padding:10px 0 0;text-align:right;font-weight:700;border-top:1px solid #e2e8f0;"><?= e($balance->format()) ?></td></tr>
</table>

<?php if ($balance->isZero()): ?>
    <p style="margin:0;color:#16a34a;font-weight:600;">This invoice is now paid in full. Thanks again for your business!</p>
<?php else: ?>
    <p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">There's still a balance of <?= e($balance->format()) ?> outstanding on this invoice.</p>
<?php endif; ?>
<?php $this->endSection(); ?>
