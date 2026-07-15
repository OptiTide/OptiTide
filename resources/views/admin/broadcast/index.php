<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="post" action="<?= route('admin.broadcast.send') ?>" novalidate onsubmit="return confirm('Send this email to the selected clients now?')">
            <?= csrf_field() ?>
            <div class="card">
                <div class="card-header"><i class="bi bi-megaphone text-brand"></i> Compose broadcast</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Send to</label>
                        <select name="audience" class="form-select">
                            <option value="all">All clients (<?= (int) $counts['all'] ?>)</option>
                            <option value="active">Active clients (<?= (int) $counts['active'] ?>)</option>
                            <option value="suspended">Suspended clients (<?= (int) $counts['suspended'] ?>)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" value="<?= e(old('subject')) ?>" class="form-control <?= has_error('subject') ? 'is-invalid' : '' ?>" maxlength="200" required>
                        <?php if (error('subject')): ?><div class="invalid-feedback"><?= e(error('subject')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="body" rows="9" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="8000" required placeholder="Write your announcement… Line breaks are preserved."><?= e(old('body')) ?></textarea>
                        <?php if (error('body')): ?><div class="invalid-feedback"><?= e(error('body')) ?></div><?php endif; ?>
                        <div class="form-text">Sent through the branded email template, personally addressed to each client.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Button text <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="cta_text" value="<?= e(old('cta_text')) ?>" class="form-control" maxlength="60" placeholder="e.g. View our new plans">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button link</label>
                            <input type="text" name="cta_url" value="<?= e(old('cta_url')) ?>" class="form-control <?= has_error('cta_url') ? 'is-invalid' : '' ?>" placeholder="https://…">
                            <?php if (error('cta_url')): ?><div class="invalid-feedback"><?= e(error('cta_url')) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-brand"><i class="bi bi-send"></i> Send Broadcast</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
