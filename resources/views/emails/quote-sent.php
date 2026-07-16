<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?? $client['business_name'] ?? 'there') ?>,</p>
<p style="margin:0 0 14px;line-height:1.6;">
    Please find attached quote <strong><?= e($quote['number']) ?></strong> from <?= e(config('company.legal_name')) ?>.
</p>

<table role="presentation" width="100%" style="margin:8px 0 20px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;">Quote</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($quote['number']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Issued</td><td style="padding:6px 0;text-align:right;"><?= e($quote['issue_date']) ?></td></tr>
    <?php if (! empty($quote['expires_at'])): ?>
        <tr><td style="padding:6px 0;color:#64748b;">Valid until</td><td style="padding:6px 0;text-align:right;"><?= e($quote['expires_at']) ?></td></tr>
    <?php endif; ?>
    <tr><td style="padding:10px 0 0;font-weight:700;border-top:1px solid #e2e8f0;">Total (inc GST)</td><td style="padding:10px 0 0;text-align:right;font-weight:700;border-top:1px solid #e2e8f0;"><?= e(\App\Models\Quote::total($quote)->format()) ?></td></tr>
</table>

<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($acceptUrl) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">View &amp; Accept Quote</a>
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">
    Accepting the quote raises the invoice so we can get started. If you have any questions, or you'd like anything adjusted, just reply to this email.
</p>
<?php $this->endSection(); ?>
