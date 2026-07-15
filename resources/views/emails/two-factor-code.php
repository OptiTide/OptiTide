<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">Use this code to finish signing in to OptiTide. It expires in 10 minutes.</p>
<p style="text-align:center;margin:0 0 18px;">
    <span style="display:inline-block;font-size:30px;font-weight:800;letter-spacing:.35em;color:#0D1530;background:#F7F7F8;border:1px solid #e5e7eb;border-radius:10px;padding:14px 26px;"><?= e($code) ?></span>
</p>
<p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">If you didn't try to sign in, you can ignore this e-mail and your account stays safe.</p>
<?php $this->endSection(); ?>
