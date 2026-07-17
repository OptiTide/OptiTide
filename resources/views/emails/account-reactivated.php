<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: $client['business_name']) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">
    Thanks — your payment has come through and your account is back to normal. Everything that was
    paused is running again, and there's nothing else you need to do.
</p>
<p style="text-align:center;margin:0 0 18px;">
    <a href="<?= e(url('portal')) ?>" style="display:inline-block;background:<?= e(config('app.brand.accent')) ?>;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-weight:600;">Open My Portal</a>
</p>
<p style="margin:0;line-height:1.6;">
    If anything still looks off, just reply to this email — it comes straight to us.
</p>
<?php $this->endSection(); ?>
