<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<?php $a = $application; ?>
<p style="margin:0 0 14px;font-size:16px;">New application received</p>
<p style="margin:0 0 18px;line-height:1.6;"><strong><?= e($a['name']) ?></strong> applied for <strong><?= e($a['role_title']) ?></strong>.</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6;margin:0 0 18px;">
    <tr><td style="color:#64748b;padding:3px 12px 3px 0;">Email</td><td><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></td></tr>
    <?php if (! empty($a['phone'])): ?><tr><td style="color:#64748b;padding:3px 12px 3px 0;">Phone</td><td><?= e($a['phone']) ?></td></tr><?php endif; ?>
    <?php if (! empty($a['location'])): ?><tr><td style="color:#64748b;padding:3px 12px 3px 0;">Based in</td><td><?= e($a['location']) ?></td></tr><?php endif; ?>
    <?php if (! empty($a['linkedin_url'])): ?><tr><td style="color:#64748b;padding:3px 12px 3px 0;">LinkedIn</td><td><?= e($a['linkedin_url']) ?></td></tr><?php endif; ?>
    <?php if (! empty($a['portfolio_url'])): ?><tr><td style="color:#64748b;padding:3px 12px 3px 0;">Portfolio</td><td><?= e($a['portfolio_url']) ?></td></tr><?php endif; ?>
    <tr><td style="color:#64748b;padding:3px 12px 3px 0;">CV</td><td><?= ! empty($a['resume_name']) ? e($a['resume_name']) : 'None attached' ?></td></tr>
</table>

<p style="margin:0 0 6px;color:#64748b;">Cover note</p>
<div style="padding:14px 16px;background:#f6f8fa;border:1px solid #e2e8f0;border-radius:8px;line-height:1.6;"><?= nl2br(e($a['cover_letter'])) ?></div>

<p style="text-align:center;margin:22px 0 0;">
    <a href="<?= e($reviewUrl) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Review &amp; download CV</a>
</p>
<p style="margin:14px 0 0;color:#94a3b8;font-size:12px;">The CV is only available from the admin console — it's never attached to email.</p>
<?php $this->endSection(); ?>
