<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>
<form method="post" action="<?= route('portal.profile.update') ?>" novalidate>
    <?= csrf_field() ?><?= method_field('PUT') ?>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Your Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= e(old('name', $user['name'])) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="<?= e(old('email', $user['email'])) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="contact_name" value="<?= e(old('contact_name', $client['contact_name'] ?? '')) ?>" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="<?= e(old('phone', $client['phone'] ?? '')) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Change Password</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" autocomplete="new-password">
                        <?php if (error('password')): ?><div class="invalid-feedback"><?= e(error('password')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="form-text mt-2">Leave the password fields blank to keep your current password.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3"><button class="btn btn-brand">Save Changes</button></div>
</form>
<?php $this->endSection(); ?>
