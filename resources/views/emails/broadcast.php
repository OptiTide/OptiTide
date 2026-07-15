<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: ($client['business_name'] ?? 'there')) ?>,</p>
<div style="margin:0 0 18px;line-height:1.6;font-size:15px;"><?= nl2br(e($body)) ?></div>
<?php if (! empty($ctaText) && ! empty($ctaUrl)): ?>
<p style="text-align:center;margin:6px 0 18px;">
    <a href="<?= e($ctaUrl) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent', '#FF6A00')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;"><?= e($ctaText) ?></a>
</p>
<?php endif; ?>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">You're receiving this because you're a <?= e(config('company.legal_name', 'OptiTide')) ?> client. Just reply if you have any questions.</p>
<?php $this->endSection(); ?>
