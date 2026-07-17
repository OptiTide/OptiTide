<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">
    <?php $what = ($isStaff ?? false) ? 'staff account' : 'client portal'; ?>
    <?php if ($isResend): ?>
        Here's a fresh link to your <?= e(config('company.brand_name')) ?> <?= $what ?><?= $business ? ' for ' . e($business) : '' ?> — the previous one has been replaced.
    <?php else: ?>
        Welcome aboard! Your <?= e(config('company.brand_name')) ?> <?= $what ?><?= $business ? ' for ' . e($business) : '' ?> is set up and ready.
    <?php endif; ?>
    Choose your password below and you're in — we never set it for you, so nobody else knows it.
</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e($url) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Set My Password</a>
</p>
<p style="margin:0 0 18px;line-height:1.6;">
    <?php if ($isStaff ?? false): ?>
        Once you're in you'll have access to the admin — clients, invoices, project boards and the helpdesk.
    <?php else: ?>
        Once you're in you can view and pay invoices, track your project, and message us any time.
    <?php endif; ?>
</p>
<p style="margin:0 0 18px;line-height:1.6;color:#64748b;font-size:13px;">
    This link works for <?= (int) $days ?> days. If it expires, just use "Forgot password" on the login page and we'll send you a new one.
</p>
<p style="margin:0;color:#64748b;font-size:12px;word-break:break-all;"><?= e($url) ?></p>
<?php $this->endSection(); ?>
