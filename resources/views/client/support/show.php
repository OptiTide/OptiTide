<?php
$this->extends('layouts.portal');
$badge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <a href="<?= route('portal.support.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> All requests</a>

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="text-muted small"><?= e($ticket['number']) ?><?= $ticket['category'] ? ' · ' . e($ticket['category']) : '' ?></div>
                    <div class="h5 fw-bold mb-0"><?= e($ticket['subject']) ?></div>
                </div>
                <span class="badge <?= $badge[$ticket['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::CLIENT_STATUSES[$ticket['status']] ?? ucfirst($ticket['status'])) ?></span>
            </div>
        </div>

        <div class="tk-thread mb-3">
            <?php foreach ($replies as $r): ?>
                <div class="tk-msg <?= $r['is_staff'] ? 'tk-msg--staff' : 'tk-msg--client' ?>">
                    <div class="tk-msg-head">
                        <span class="fw-semibold"><?= $r['is_staff'] ? config('company.brand_name') . ' Support' : 'You' ?></span>
                        <span class="text-muted small"><?= e($r['created_at'] ? date('d M Y, g:ia', strtotime($r['created_at'])) : '') ?></span>
                    </div>
                    <div class="tk-msg-body"><?= nl2br(e($r['body'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ticket['status'] !== 'closed'): ?>
            <form method="post" action="<?= route('portal.support.reply', ['id' => $ticket['id']]) ?>" class="card">
                <?= csrf_field() ?>
                <div class="card-body">
                    <label class="form-label fw-semibold">Add a reply</label>
                    <textarea name="body" rows="4" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="5000" required placeholder="Type your message…"><?= e(old('body')) ?></textarea>
                    <?php if (error('body')): ?><div class="invalid-feedback"><?= e(error('body')) ?></div><?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-brand"><i class="bi bi-send"></i> Send Reply</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-secondary">This request is closed. Need more help? <a href="<?= route('portal.support.create') ?>">Open a new request</a>.</div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>
