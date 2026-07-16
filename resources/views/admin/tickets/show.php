<?php
$this->extends('layouts.admin');
$badge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<a href="<?= route('admin.tickets.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> All Tickets</a>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="text-muted small"><?= e($ticket['number']) ?><?= $ticket['category'] ? ' · ' . e($ticket['category']) : '' ?></div>
                    <div class="h5 fw-bold mb-0"><?= e($ticket['subject']) ?></div>
                </div>
                <span class="badge <?= $badge[$ticket['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::STATUSES[$ticket['status']] ?? ucfirst($ticket['status'])) ?></span>
            </div>
        </div>

        <?php // Reply box first, then newest -> oldest, matching the client view. ?>
        <form method="post" action="<?= route('admin.tickets.reply', ['id' => $ticket['id']]) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-body">
                <label class="form-label fw-semibold" for="admin_reply">Reply</label>
                <textarea id="admin_reply" name="body" rows="4" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="5000" required placeholder="Type your reply…"><?= e(old('body')) ?></textarea>
                <?php if (error('body')): ?><div class="invalid-feedback"><?= e(error('body')) ?></div><?php endif; ?>
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_internal" value="1" class="form-check-input" id="internal">
                    <label class="form-check-label" for="internal">Internal note — only staff can see this (never sent to the client)</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-brand"><i class="bi bi-send"></i> Post</button>
            </div>
        </form>

        <div class="tk-thread mb-3">
            <?php foreach ($replies as $i => $r): ?>
                <?php $isInternal = ! empty($r['is_internal']); ?>
                <div class="tk-msg <?= $isInternal ? 'tk-msg--internal' : ($r['is_staff'] ? 'tk-msg--staff' : 'tk-msg--client') ?>">
                    <div class="tk-msg-head">
                        <span class="fw-semibold">
                            <?= e($authors[$r['user_id']] ?? ($r['is_staff'] ? 'Staff' : 'Client')) ?>
                            <?php if ($isInternal): ?><span class="badge text-bg-warning ms-1">Internal note</span>
                            <?php elseif ($r['is_staff']): ?><span class="badge badge-soft ms-1">Staff</span>
                            <?php else: ?><span class="badge badge-soft ms-1">Client</span><?php endif; ?>
                        </span>
                        <span class="text-muted small">
                            <?php if ($i === 0 && count($replies) > 1): ?><span class="badge text-bg-light me-1">Latest</span><?php endif; ?>
                            <?php if ($i === count($replies) - 1 && count($replies) > 1): ?><span class="badge text-bg-light me-1">Original request</span><?php endif; ?>
                            <?= e($r['created_at'] ? date('d M Y, g:ia', strtotime($r['created_at'])) : '') ?>
                        </span>
                    </div>
                    <div class="tk-msg-body"><?= nl2br(e($r['body'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Details</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Client</span><span class="fw-semibold text-end"><?= $client ? e($client['business_name']) : 'No client' ?></span></div>
                <?php if ($client): ?>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Contact</span><span class="text-end"><?= e($client['contact_name'] ?? '—') ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">E-mail</span><span class="text-end"><?= e($client['email'] ?? '—') ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Priority</span><span class="text-end"><?= e(\App\Models\Ticket::PRIORITIES[$ticket['priority']] ?? ucfirst($ticket['priority'])) ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Opened</span><span class="text-end"><?= e($ticket['created_at'] ? date('d M Y', strtotime($ticket['created_at'])) : '—') ?></span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Status</div>
            <div class="card-body">
                <form method="post" action="<?= route('admin.tickets.status', ['id' => $ticket['id']]) ?>" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (\App\Models\Ticket::STATUSES as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $ticket['status'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-brand">Set</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
