<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">New meeting request</p>
<table role="presentation" width="100%" style="margin:8px 0 18px;border-collapse:collapse;">
    <tr><td style="padding:6px 0;color:#64748b;">Client</td><td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($client['business_name'] ?? 'Client') ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Topic</td><td style="padding:6px 0;text-align:right;"><?= e($title) ?></td></tr>
    <tr><td style="padding:6px 0;color:#64748b;">Preferred time</td><td style="padding:6px 0;text-align:right;"><?= e(date('l j F Y, g:ia', strtotime($meeting_at))) ?></td></tr>
</table>
<p style="margin:0;color:#64748b;font-size:13px;">Confirm it from Admin → Meetings.</p>
<?php $this->endSection(); ?>
