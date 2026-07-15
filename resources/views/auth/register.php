<?php $this->extends('layouts.auth'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 mb-1">Create Your Account</h1>
        <p class="text-muted small mb-4">Set up a client login to view and pay your invoices.</p>
        <form method="post" action="<?= route('register') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Your Name</label>
                <input type="text" name="name" value="<?= e(old('name')) ?>"
                       class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required autofocus>
                <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Business Name</label>
                <input type="text" name="business_name" value="<?= e(old('business_name')) ?>"
                       class="form-control <?= has_error('business_name') ? 'is-invalid' : '' ?>" required>
                <?php if (error('business_name')): ?><div class="invalid-feedback"><?= e(error('business_name')) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="<?= e(old('email')) ?>"
                       class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
            </div>
            <div class="row g-2 mb-3">
                <div class="col">
                    <label class="form-label">Password</label>
                    <input type="password" name="password"
                           class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" required>
                    <?php if (error('password')): ?><div class="invalid-feedback"><?= e(error('password')) ?></div><?php endif; ?>
                </div>
                <div class="col">
                    <label class="form-label">Confirm</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <button class="btn btn-brand w-100">Create Account</button>
        </form>
        <p class="text-center small mt-3 mb-0">Already have an account? <a href="<?= route('login') ?>">Sign In</a></p>
    </div>
</div>
<?php $this->endSection(); ?>
