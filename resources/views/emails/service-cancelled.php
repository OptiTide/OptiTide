<?php $this->extends('emails.layout'); ?>
<?php $this->section('content'); ?>
<p style="margin:0 0 14px;font-size:16px;">Hi <?= e($client['contact_name'] ?: $client['business_name']) ?>,</p>
<p style="margin:0 0 18px;line-height:1.6;">
    This confirms that <strong><?= e($label) ?></strong> has been cancelled. You won't be billed for it again,
    and there's nothing further you need to do.
</p>
<p style="margin:0 0 18px;line-height:1.6;">
    Any invoice already issued still stands, and anything you've paid for runs to the end of its period.
</p>
<p style="margin:0 0 18px;line-height:1.6;">
    If this wasn't meant to happen, or you'd like to pick it back up later, just reply to this email —
    it comes straight to us.
</p>
<p style="margin:0;line-height:1.6;">Thanks for having us on board.</p>
<?php $this->endSection(); ?>
