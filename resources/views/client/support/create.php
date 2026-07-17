<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="<?= route('portal.support.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Back to Support</a>
        <form method="post" action="<?= route('portal.support.store') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="card">
                <div class="card-header">New Support Request</div>
                <div class="card-body">
                    <p class="text-muted">Tell us what you need and we'll get back to you. You'll see our reply here and we'll email you when it lands.</p>
                    <div class="mb-3">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" value="<?= e(old('subject', $subject)) ?>" class="form-control <?= has_error('subject') ? 'is-invalid' : '' ?>" maxlength="200" required autofocus placeholder="e.g. Change the phone number on my website">
                        <?php if (error('subject')): ?><div class="invalid-feedback"><?= e(error('subject')) ?></div><?php endif; ?>
                        <div class="form-text">A short summary — one line is plenty.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" for="category">What's it about?</label>
                            <?php $selectedCategory = old('category', $category); ?>
                            <select name="category" id="category" class="form-select">
                                <option value="">Something else</option>
                                <?php foreach (\App\Models\Ticket::CATEGORIES as $cat): ?>
                                    <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="priority">How urgent is it?</label>
                            <?php $p = old('priority', 'normal'); ?>
                            <select name="priority" id="priority" class="form-select">
                                <?php foreach (\App\Models\Ticket::PRIORITIES as $k => $lbl): ?>
                                    <option value="<?= $k ?>" <?= $p === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="body">How can we help?</label>
                        <textarea name="body" id="body" rows="6" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="5000" required placeholder="Give us as much detail as you can — links, page names, or what you expected to happen."><?= e(old('body')) ?></textarea>
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
