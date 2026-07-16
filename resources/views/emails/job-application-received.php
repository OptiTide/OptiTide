<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">
    Thanks for applying<?= $role ? ' for <strong>' . e($role) . '</strong>' : '' ?> — we've got it.
</p>
<p style="margin:0 0 18px;line-height:1.6;">
    We're a small team and a real person reads every application, so it can take us a few days to get back to you.
    If it looks like a fit we'll be in touch to organise a chat. Either way, we'll let you know.
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">
    Thanks for your interest,<br>The <?= e(config('company.brand_name')) ?> Team
</p>
<?php $this->endSection(); ?>
