<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">Thanks for reaching out to OptiTide — we've received your enquiry and one of our team will get back to you shortly, usually within one business day.</p>
<p style="margin:0 0 6px;color:#64748b;">For your records, here's what you sent us<?= ! empty($service) ? ' about <strong>' . e($service) . '</strong>' : '' ?>:</p>
<div style="padding:14px 16px;background:#f6f8fa;border:1px solid #e2e8f0;border-radius:8px;line-height:1.6;"><?= nl2br(e($message)) ?></div>
<p style="margin:16px 0 0;color:#64748b;font-size:13px;line-height:1.6;">Talk soon,<br>The OptiTide Team</p>
<?php $this->endSection(); ?>
