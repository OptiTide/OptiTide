<?php
$this->extends('layouts.admin');
$isEdit = $user !== null;
$action = $isEdit ? route('admin.users.update', ['id' => $user['id']]) : route('admin.users.store');
$role = old('role', $user['role'] ?? 'client');
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= e(old('name', $user['name'] ?? '')) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <?= $isEdit ? '<span class="text-muted small">(leave blank to keep)</span>' : '' ?></label>
                        <input type="password" name="password" class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
                        <?php if (error('password')): ?><div class="invalid-feedback"><?= e(error('password')) ?></div><?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" onchange="document.getElementById('clientRow').style.display = this.value==='client' ? '' : 'none'">
                                <?php foreach (\App\Models\User::ROLES as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $role === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($isEdit): ?>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3" id="clientRow" style="display:<?= $role === 'client' ? '' : 'none' ?>">
                        <label class="form-label">Linked Client</label>
                        <select name="client_id" class="form-select <?= has_error('client_id') ? 'is-invalid' : '' ?>">
                            <option value="">Select a client…</option>
                            <?php $selC = old('client_id', $user['client_id'] ?? ''); ?>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (string) $selC === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (error('client_id')): ?><div class="invalid-feedback d-block"><?= e(error('client_id')) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-brand"><?= $isEdit ? 'Save Changes' : 'Create User' ?></button>
                    <a href="<?= route('admin.users.index') ?>" class="btn btn-link">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->endSection(); ?>
