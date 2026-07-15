<?php $this->extends('layouts.auth'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 mb-4">Choose a new password</h1>
        <form method="post" action="<?= route('password.update') ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" value="<?= e($email) ?>"
                       class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="password"
                       class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" required autofocus>
                <?php if (error('password')): ?><div class="invalid-feedback"><?= e(error('password')) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button class="btn btn-brand w-100">Reset password</button>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
