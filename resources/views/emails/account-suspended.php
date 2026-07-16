<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: ($client['business_name'] ?? 'there')) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">We've had to place your <?= e(config('company.brand_name')) ?> account <strong>on hold</strong> because one or more invoices have remained unpaid past their due date. Your services have been paused for now.</p>
<p style="margin:0 0 18px;line-height:1.6;">The good news: as soon as your outstanding balance is paid, your account and services are switched straight back on automatically.</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e(url('portal/invoices')) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent', '#FF6A00')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">View &amp; Pay Invoices</a>
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">If you think this is a mistake, or you'd like to arrange a payment plan, just reply to this email and we'll sort it out with you.</p>
<?php $this->endSection(); ?>
