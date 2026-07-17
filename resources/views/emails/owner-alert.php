<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 18px;font-size:17px;font-weight:600;"><?= e($headline) ?></p>
<?php if ($rows): ?>
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 18px;border-collapse:collapse;">
        <?php foreach ($rows as $label => $value): ?>
            <tr>
                <td style="padding:7px 12px 7px 0;color:#64748b;font-size:13px;white-space:nowrap;vertical-align:top;"><?= e($label) ?></td>
                <td style="padding:7px 0;font-size:14px;font-weight:600;color:#1f2637;"><?= e($value) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php if ($url): ?>
    <p style="text-align:center;margin:0 0 6px;">
        <a href="<?= e($url) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;"><?= e($cta ?: 'Open in the CRM') ?></a>
    </p>
<?php endif; ?>
<p style="margin:18px 0 0;color:#94a3b8;font-size:12px;">Internal notification — the client did not receive this.</p>
<?php $this->endSection(); ?>
