<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">
    We received a request to reset your <?= e(config('company.brand_name')) ?> password. Click below to choose a new one.
    This link expires in 60 minutes. If you didn't request this, you can ignore this email.
</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($url) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Reset Password</a>
</p>
<p style="margin:0;color:#64748b;font-size:12px;word-break:break-all;"><?= e($url) ?></p>
<?php $this->endSection(); ?>
