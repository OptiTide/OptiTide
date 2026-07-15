<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Please Review &amp; Accept</div>
            <div class="card-body">
                <p>Before you continue, please review and accept our terms. This only takes a moment.</p>
                <ul>
                    <li><a href="<?= route('legal.terms') ?>" target="_blank">Terms of Service</a></li>
                    <li><a href="<?= route('legal.privacy') ?>" target="_blank">Privacy Policy</a></li>
                    <li><a href="<?= route('legal.refund') ?>" target="_blank">Refund &amp; Cancellation Policy</a></li>
                </ul>
                <form method="post" action="<?= route('portal.terms.accept') ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="accept_terms" value="1" id="accept" class="form-check-input" required>
                        <label class="form-check-label" for="accept">I have read and accept the Terms of Service, Privacy Policy and Refund Policy.</label>
                    </div>
                    <button class="btn btn-brand">Accept &amp; Continue</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
