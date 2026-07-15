<?php
$this->extends('layouts.portal');
$c = fn (string $key, $default = '') => e(old($key, $client[$key] ?? $default));
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= route('portal.profile.update') ?>" novalidate>
    <?= csrf_field() ?><?= method_field('PUT') ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Personal &amp; Login</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" value="<?= e(old('name', $user['name'])) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">E-Mail (Login)</label>
                        <input type="email" name="email" value="<?= e(old('email', $user['email'])) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
                        <div class="form-text">This is the e-mail you sign in with.</div>
                    </div>
                </div>
            </div>

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

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Business Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" value="<?= $c('business_name') ?>" class="form-control <?= has_error('business_name') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('business_name')): ?><div class="invalid-feedback"><?= e(error('business_name')) ?></div><?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ABN</label>
                            <input type="text" name="abn" value="<?= $c('abn') ?>" class="form-control" placeholder="12 345 678 901">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ACN <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="acn" value="<?= $c('acn') ?>" class="form-control" placeholder="123 456 789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="contact_name" value="<?= $c('contact_name') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Phone</label>
                            <input type="text" name="phone" value="<?= $c('phone') ?>" class="form-control" placeholder="0400 000 000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business E-Mail</label>
                            <input type="email" name="business_email" value="<?= $c('email') ?>" class="form-control <?= has_error('business_email') ? 'is-invalid' : '' ?>">
                            <?php if (error('business_email')): ?><div class="invalid-feedback"><?= e(error('business_email')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" value="<?= $c('website') ?>" class="form-control <?= has_error('website') ? 'is-invalid' : '' ?>" placeholder="https://">
                            <?php if (error('website')): ?><div class="invalid-feedback"><?= e(error('website')) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div class="text-muted small text-uppercase mb-2" style="letter-spacing:.05em">Business Address</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Street</label>
                            <input type="text" name="address_line1" value="<?= $c('address_line1') ?>" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Suburb / City</label>
                            <input type="text" name="address_locality" value="<?= $c('address_locality') ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <select name="address_region" class="form-select">
                                <option value="">—</option>
                                <?php $sel = old('address_region', $client['address_region'] ?? ''); ?>
                                <?php foreach ($states as $st): ?>
                                    <option value="<?= $st ?>" <?= $sel === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Postcode</label>
                            <input type="text" name="address_postcode" value="<?= $c('address_postcode') ?>" class="form-control" maxlength="4" placeholder="2000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" value="Australia" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3"><button class="btn btn-brand btn-lg">Save Changes</button></div>
</form>
<?php $this->endSection(); ?>
