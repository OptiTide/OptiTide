<?php
$this->extends('layouts.admin');
$isEdit = $client !== null;
$action = $isEdit ? route('admin.clients.update', ['id' => $client['id']]) : route('admin.clients.store');
$val = fn (string $key, $default = '') => e(old($key, $client[$key] ?? $default));
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Business Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" value="<?= $val('business_name') ?>" class="form-control <?= has_error('business_name') ? 'is-invalid' : '' ?>" required autofocus>
                            <?php if (error('business_name')): ?><div class="invalid-feedback"><?= e(error('business_name')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ABN</label>
                            <input type="text" name="abn" value="<?= $val('abn') ?>" class="form-control" placeholder="12 345 678 901">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="contact_name" value="<?= $val('contact_name') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-Mail</label>
                            <input type="email" name="email" value="<?= $val('email') ?>" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>">
                            <?php if (error('email')): ?><div class="invalid-feedback"><?= e(error('email')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="<?= $val('phone') ?>" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Address</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Street</label>
                            <input type="text" name="address_line1" value="<?= $val('address_line1') ?>" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Suburb / City</label>
                            <input type="text" name="address_locality" value="<?= $val('address_locality') ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="address_region" value="<?= $val('address_region') ?>" class="form-control" placeholder="NSW">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Postcode</label>
                            <input type="text" name="address_postcode" value="<?= $val('address_postcode') ?>" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <?php if ($isEdit): ?>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select mb-3">
                            <option value="active" <?= ($client['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="archived" <?= ($client['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    <?php endif; ?>
                    <?php if (! $isEdit): ?>
                        <?php // Porting: without this every ported client reads as having
                              // joined today, and "client since" on their page is a lie. ?>
                        <label class="form-label">Client Since <span class="text-muted fw-normal small">(optional)</span></label>
                        <input type="date" name="created_at" value="<?= e(old('created_at')) ?>" class="form-control mb-1" max="<?= e(today()) ?>">
                        <div class="form-text mb-3">Only set this when porting an existing client in. Leave blank for today.</div>

                        <?php // On by default: adding a client almost always means giving them
                              // the portal. Untick to add them quietly and invite later from
                              // the client's own page. ?>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="send_invite" name="send_invite" value="1" checked>
                            <label class="form-check-label" for="send_invite">
                                <strong>Email a portal invite</strong>
                                <span class="d-block text-muted small">They choose their own password — we never see it. The link lasts 7 days.</span>
                            </label>
                        </div>
                    <?php endif; ?>
                    <button class="btn btn-brand w-100"><?= $isEdit ? 'Save Changes' : 'Create Client' ?></button>
                    <a href="<?= $isEdit ? route('admin.clients.show', ['id' => $client['id']]) : route('admin.clients.index') ?>" class="btn btn-link w-100">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->endSection(); ?>
