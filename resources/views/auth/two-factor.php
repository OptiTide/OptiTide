<?php $this->extends('layouts.auth'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 mb-1">Two-Step Verification</h1>
        <p class="text-muted small mb-4">
            <?= $method === 'email'
                ? 'Enter the 6-digit code we just sent to your e-mail.'
                : 'Enter the 6-digit code from your authenticator app.' ?>
        </p>
        <form method="post" action="<?= route('2fa.verify') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="20"
                       class="form-control form-control-lg text-center" style="letter-spacing:.35em; font-weight:600"
                       placeholder="000000" required autofocus>
            </div>
            <button class="btn btn-brand w-100">Verify</button>
        </form>
        <?php if ($method === 'email'): ?>
            <form method="post" action="<?= route('2fa.resend') ?>" class="text-center mt-3">
                <?= csrf_field() ?>
                <button class="btn btn-link btn-sm">Resend code</button>
            </form>
        <?php endif; ?>
        <p class="text-center text-muted small mt-2 mb-0">Lost your device? Enter one of your recovery codes above.</p>
        <p class="text-center small mt-3 mb-0"><a href="<?= route('login') ?>">Cancel and sign in again</a></p>
    </div>
</div>
<?php $this->endSection(); ?>
