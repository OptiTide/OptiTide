<?php $this->extends('layouts.auth'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 mb-1">Welcome Back</h1>
        <p class="text-muted small mb-4">Sign in to your OptiTide account.</p>
        <form method="post" action="<?= route('login') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="<?= e(old('email')) ?>"
                       class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required autofocus>
                <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" required>
                <?php if (error('password')): ?><div class="invalid-feedback"><?= e(error('password')) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-brand w-100">Sign In</button>
        </form>
        <div class="d-flex justify-content-between mt-3 small">
            <a href="<?= route('password.request') ?>">Forgot Password?</a>
            <a href="<?= route('register') ?>">Create an Account</a>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
