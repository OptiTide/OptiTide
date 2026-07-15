<?php
$this->extends('layouts.admin');
$isEdit = $service !== null;
$action = $isEdit ? route('admin.services.update', ['id' => $service['id']]) : route('admin.services.store');
$currency = config('company.currency', 'AUD');
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= e(old('name', $service['name'] ?? '')) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required autofocus>
                        <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Line</label>
                        <select name="category_id" class="form-select">
                            <option value="">Uncategorised</option>
                            <?php $selCat = old('category_id', $service['category_id'] ?? ''); ?>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (string) $selCat === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control"><?= e(old('description', $service['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Billing</label>
                            <?php $bt = old('billing_type', $service['billing_type'] ?? 'one_off'); ?>
                            <select name="billing_type" class="form-select">
                                <option value="one_off" <?= $bt === 'one_off' ? 'selected' : '' ?>>One-off</option>
                                <option value="recurring" <?= $bt === 'recurring' ? 'selected' : '' ?>>Recurring</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interval</label>
                            <?php $iv = old('interval', $service['interval'] ?? 'monthly'); ?>
                            <select name="interval" class="form-select">
                                <?php foreach (\App\Models\Service::INTERVALS as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $iv === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Used only for recurring services.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (<?= e($currency) ?>, inc GST)</label>
                            <input type="number" step="0.01" name="price" value="<?= e(old('price', isset($service['price_cents']) ? number_format($service['price_cents'] / 100, 2, '.', '') : '')) ?>" class="form-control <?= has_error('price') ? 'is-invalid' : '' ?>" required>
                            <?php if (error('price')): ?><div class="invalid-feedback"><?= e(error('price')) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" name="active" value="1" id="active" <?= old('active', $service['active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Active (available to add to invoices)</label>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-brand"><?= $isEdit ? 'Save Changes' : 'Create Service' ?></button>
                    <a href="<?= route('admin.services.index') ?>" class="btn btn-link">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->endSection(); ?>
