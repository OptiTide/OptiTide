<?php $this->extends('layouts.auth'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 mb-1">Reset Your Password</h1>
        <p class="text-muted small mb-4">Enter your email and we'll send you a reset link.</p>
        <form method="post" action="<?= route('password.email') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="<?= e(old('email')) ?>"
                       class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required autofocus>
                <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-brand w-100">Send Reset Link</button>
        </form>
        <p class="text-center small mt-3 mb-0"><a href="<?= route('login') ?>">Back to sign in</a></p>
    </div>
</div>
<?php $this->endSection(); ?>
