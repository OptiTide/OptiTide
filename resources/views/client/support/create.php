<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="<?= route('portal.support.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Back to support</a>
        <form method="post" action="<?= route('portal.support.store') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="card">
                <div class="card-header">New support request</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" value="<?= e(old('subject')) ?>" class="form-control <?= has_error('subject') ? 'is-invalid' : '' ?>" maxlength="200" required autofocus>
                        <?php if (error('subject')): ?><div class="invalid-feedback"><?= e(error('subject')) ?></div><?php endif; ?>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">General</option>
                                <?php foreach (\App\Models\Ticket::CATEGORIES as $cat): ?>
                                    <option value="<?= e($cat) ?>" <?= old('category') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Priority</label>
                            <?php $p = old('priority', 'normal'); ?>
                            <select name="priority" class="form-select">
                                <?php foreach (\App\Models\Ticket::PRIORITIES as $k => $lbl): ?>
                                    <option value="<?= $k ?>" <?= $p === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">How can we help?</label>
                        <textarea name="body" rows="6" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="5000" required><?= e(old('body')) ?></textarea>
                        <?php if (error('body')): ?><div class="invalid-feedback"><?= e(error('body')) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="<?= route('portal.support.index') ?>" class="btn btn-light">Cancel</a>
                    <button class="btn btn-brand"><i class="bi bi-send"></i> Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
