<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">Thanks for joining OptiTide! Please confirm this is your email address so we can keep your account secure and send you invoices, updates and receipts.</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($url) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Confirm my email</a>
</p>
<p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:1.6;">Or copy and paste this link into your browser:</p>
<p style="margin:0 0 18px;font-size:12px;word-break:break-all;"><a href="<?= e($url) ?>" style="color:<?= e(config('app.brand.accent')) ?>;"><?= e($url) ?></a></p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">If you didn't create an OptiTide account, you can safely ignore this email.</p>
<?php $this->endSection(); ?>
