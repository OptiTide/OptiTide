<?php
$this->extends('emails.layout');
$balance = \App\Models\Invoice::balance($invoice);
?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?? $client['business_name'] ?? 'there') ?>,</p>
<p style="margin:0 0 14px;line-height:1.6;">
    Please find attached invoice <strong><?= e($invoice['number']) ?></strong> from <?= e(config('company.legal_name')) ?>.
</p>

<table role="presentation" width="100%" style="margin:8px 0 20px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;">Invoice</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($invoice['number']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Issued</td><td style="padding:6px 0;text-align:right;"><?= e($invoice['issue_date']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Due</td><td style="padding:6px 0;text-align:right;"><?= e($invoice['due_date']) ?></td></tr>
    <tr><td style="padding:10px 0 0;font-weight:700;border-top:1px solid #e2e8f0;">Balance due</td><td style="padding:10px 0 0;text-align:right;font-weight:700;border-top:1px solid #e2e8f0;"><?= e($balance->format()) ?></td></tr>
</table>

<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($payUrl) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">View &amp; Pay Invoice</a>
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">
    You can pay by PayID/bank transfer or Payoneer from the invoice page. If you have any questions just reply to this email.
</p>
<?php $this->endSection(); ?>
