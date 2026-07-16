<?php
$this->extends('layouts.admin');
$priority = old('priority', $card['priority'] ?? \App\Models\BoardCard::PRIORITY_NORMAL);
$isComplete = ! empty($card['completed_at']);
?>
<?php $this->section('content'); ?>

<a href="<?= route('admin.boards.show', ['key' => $board['key']]) ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Back To <?= e($board['name']) ?></a>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="post" action="<?= route('admin.cards.update', ['id' => $card['id']]) ?>" class="card mb-3">
            <?= csrf_field() ?><?= method_field('PUT') ?>
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Card Details</span>
                <span class="d-flex align-items-center gap-2">
                    <?php if ($isComplete): ?><span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> Complete</span><?php endif; ?>
                    <?php if (! ($card['client_visible'] ?? 1)): ?><span class="badge text-bg-warning"><i class="bi bi-eye-slash"></i> Internal</span><?php endif; ?>
                    <span class="badge badge-soft"><?= e($column['name'] ?? '—') ?></span>
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="cf_title">Title</label>
                    <input type="text" name="title" id="cf_title" class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" maxlength="200" required value="<?= e(old('title', $card['title'])) ?>">
                    <?php if (error('title')): ?><div class="invalid-feedback"><?= e(error('title')) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cf_notes">Notes</label>
                    <textarea name="notes" id="cf_notes" rows="4" class="form-control <?= has_error('notes') ? 'is-invalid' : '' ?>" maxlength="2000"><?= e(old('notes', $card['notes'] ?? '')) ?></textarea>
                    <?php if (error('notes')): ?><div class="invalid-feedback"><?= e(error('notes')) ?></div><?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="cf_client">Client</label>
                        <select name="client_id" id="cf_client" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (string) old('client_id', $card['client_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cf_assigned">Assigned To</label>
                        <select name="assigned_to" id="cf_assigned" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (string) old('assigned_to', $card['assigned_to'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cf_due">Due Date</label>
                        <input type="date" name="due_date" id="cf_due" class="form-control" value="<?= e(old('due_date', $card['due_date'] ? date('Y-m-d', strtotime($card['due_date'])) : '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cf_priority">Priority</label>
                        <select name="priority" id="cf_priority" class="form-select">
                            <?php foreach (\App\Models\BoardCard::PRIORITIES as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $priority === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <hr class="my-3">
                <div class="form-check form-switch mb-2">
                    <input type="checkbox" name="client_visible" value="1" class="form-check-input" id="cf_visible" <?= ($card['client_visible'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cf_visible">Visible to client</label>
                    <div class="form-text">Turn this off to keep the card on this board only — the client never sees it in their project view.</div>
                </div>
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_complete" value="1" class="form-check-input" id="cf_complete" <?= $isComplete ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cf_complete">Mark as complete</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-brand">Save Card</button>
            </div>
        </form>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Checklist</span>
                <span class="text-muted small"><?= $progress['done'] ?>/<?= $progress['total'] ?> done</span>
            </div>
            <div class="card-body">
                <?php if ($progress['total'] > 0): ?>
                    <div class="progress mb-3" style="height: 6px;" role="progressbar" aria-valuenow="<?= $progress['pct'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Checklist progress">
                        <div class="progress-bar bg-success" style="width: <?= $progress['pct'] ?>%"></div>
                    </div>
                <?php endif; ?>

                <?php foreach ($checklist as $item): ?>
                    <div class="cl-item">
                        <form method="post" action="<?= route('admin.cards.checklist.toggle', ['id' => $item['id']]) ?>" class="cl-item-tick">
                            <?= csrf_field() ?>
                            <input type="checkbox" class="form-check-input mt-0" id="cl_<?= $item['id'] ?>" onchange="this.form.submit()" <?= ! empty($item['done']) ? 'checked' : '' ?>>
                            <label class="cl-item-text <?= ! empty($item['done']) ? 'cl-item-text--done' : '' ?>" for="cl_<?= $item['id'] ?>"><?= e($item['text']) ?></label>
                        </form>
                        <form method="post" action="<?= route('admin.cards.checklist.destroy', ['id' => $item['id']]) ?>">
                            <?= csrf_field() ?><?= method_field('DELETE') ?>
                            <button class="btn btn-sm btn-light cl-item-del" title="Delete item"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <form method="post" action="<?= route('admin.cards.checklist.store', ['id' => $card['id']]) ?>" class="d-flex gap-2 mt-3">
                    <?= csrf_field() ?>
                    <input type="text" name="text" class="form-control form-control-sm <?= has_error('text') ? 'is-invalid' : '' ?>" maxlength="300" required placeholder="Add an item…" autocomplete="off" aria-label="Checklist item">
                    <button class="btn btn-sm btn-outline-brand text-nowrap"><i class="bi bi-plus-lg"></i> Add</button>
                </form>
                <?php if (error('text')): ?><div class="text-danger small mt-1"><?= e(error('text')) ?></div><?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?= route('admin.cards.comments.store', ['id' => $card['id']]) ?>" class="card mb-3">
            <?= csrf_field() ?>
            <div class="card-body">
                <label class="form-label fw-semibold" for="card_comment">Comment</label>
                <textarea id="card_comment" name="body" rows="3" class="form-control <?= has_error('body') ? 'is-invalid' : '' ?>" maxlength="5000" required placeholder="Add an update…"><?= e(old('body')) ?></textarea>
                <?php if (error('body')): ?><div class="invalid-feedback"><?= e(error('body')) ?></div><?php endif; ?>
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_internal" value="1" class="form-check-input" id="card_internal">
                    <label class="form-check-label" for="card_internal">Internal note — only staff can see this (never shown to the client)</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-brand"><i class="bi bi-send"></i> Post</button>
            </div>
        </form>

        <?php // Newest first — the latest word on a card is what you want to land on. ?>
        <div class="tk-thread mb-3">
            <?php foreach ($comments as $comment): ?>
                <?php $isInternal = ! empty($comment['is_internal']); ?>
                <div class="tk-msg <?= $isInternal ? 'tk-msg--internal' : 'tk-msg--staff' ?>">
                    <div class="tk-msg-head">
                        <span class="fw-semibold">
                            <?= e($authors[$comment['user_id']] ?? 'Staff') ?>
                            <?php if ($isInternal): ?><span class="badge text-bg-warning ms-1">Internal note</span>
                            <?php else: ?><span class="badge badge-soft ms-1">Client can see this</span><?php endif; ?>
                        </span>
                        <span class="text-muted small"><?= e($comment['created_at'] ? date('d M Y, g:ia', strtotime($comment['created_at'])) : '') ?></span>
                    </div>
                    <div class="tk-msg-body"><?= nl2br(e($comment['body'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($comments === []): ?>
            <p class="text-muted small">No comments on this card yet.</p>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Summary</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Board</span><span class="fw-semibold text-end"><?= e($board['name']) ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Column</span><span class="text-end"><?= e($column['name'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Priority</span><span class="text-end"><span class="badge <?= e(\App\Models\BoardCard::priorityBadge($card['priority'] ?? null)) ?>"><?= e(\App\Models\BoardCard::priorityLabel($card['priority'] ?? null)) ?></span></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Due</span><span class="text-end"><?= $card['due_date'] ? e(date('d M Y', strtotime($card['due_date']))) : '—' ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Completed</span><span class="text-end"><?= $isComplete ? e(date('d M Y', strtotime($card['completed_at']))) : '—' ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Created</span><span class="text-end"><?= $card['created_at'] ? e(date('d M Y', strtotime($card['created_at']))) : '—' ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Danger Zone</div>
            <div class="card-body">
                <p class="text-muted small">Deleting a card also removes its checklist and comments. This cannot be undone.</p>
                <form method="post" action="<?= route('admin.cards.destroy', ['id' => $card['id']]) ?>" onsubmit="return confirm('Delete this card, its checklist and its comments?');">
                    <?= csrf_field() ?><?= method_field('DELETE') ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete Card</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
