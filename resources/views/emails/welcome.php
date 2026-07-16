<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">Welcome to <?= e(config('company.brand_name')) ?>! Your account is all set up. From your client portal you can view your services, see and pay invoices, download receipts, and update your details any time.</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($url) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Go to Your Portal</a>
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">If there's anything we can help with, just reply to this e-mail — we'd love to hear from you.</p>
<?php $this->endSection(); ?>
