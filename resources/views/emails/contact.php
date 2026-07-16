<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">New Website Enquiry</p>
<table role="presentation" width="100%" style="margin:8px 0 18px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;width:130px;">Name</td><td style="padding:6px 0;font-weight:600;"><?= e($data['name']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">E-Mail</td><td style="padding:6px 0;"><a href="mailto:<?= e($data['email']) ?>"><?= e($data['email']) ?></a></td></tr>
    <?php if (! empty($data['phone'])): ?>
        <tr><td style="padding:6px 0;color:#64748b;">Phone</td><td style="padding:6px 0;"><?= e($data['phone']) ?></td></tr>
    <?php endif; ?>
    <?php if (! empty($data['service'])): ?>
        <tr><td style="padding:6px 0;color:#64748b;">Interested in</td><td style="padding:6px 0;"><?= e($data['service']) ?></td></tr>
    <?php endif; ?>
</table>
<p style="margin:0 0 6px;color:#64748b;">Message</p>
<div style="padding:14px 16px;background:#f6f8fa;border:1px solid #e2e8f0;border-radius:8px;line-height:1.6;"><?= nl2br(e($data['message'])) ?></div>
<p style="margin:16px 0 0;color:#94a3b8;font-size:12px;">Sent from the <?= e(config('company.brand_name')) ?> website · <?= e($ip) ?></p>
<?php $this->endSection(); ?>
