<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: ($client['business_name'] ?? 'there')) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">You're invited to a meeting with the <?= e(config('company.brand_name')) ?> team.</p>

<table role="presentation" width="100%" style="margin:8px 0 20px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;">What</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($meeting['title']) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">When</td><td style="padding:6px 0;text-align:right;"><?= e(date('l j F Y, g:ia', strtotime($meeting['meeting_at']))) ?></td></tr>
    <?php if (! empty($meeting['description'])): ?>
        <tr><td style="padding:6px 0;color:#64748b;vertical-align:top;">Details</td><td style="padding:6px 0;text-align:right;"><?= nl2br(e($meeting['description'])) ?></td></tr>
    <?php endif; ?>
</table>

<?php if (! empty($meeting['location']) && str_starts_with((string) $meeting['location'], 'http')): ?>
    <p style="text-align:center;margin:0 0 18px;">
        <a href="<?= e($meeting['location']) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent', '#FF6A00')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Join the Meeting</a>
    </p>
<?php elseif (! empty($meeting['location'])): ?>
    <p style="margin:0 0 18px;"><strong>Where:</strong> <?= e($meeting['location']) ?></p>
<?php endif; ?>

<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">You can also see this meeting anytime in your client portal. Need to reschedule? Just reply to this email.</p>
<?php $this->endSection(); ?>
