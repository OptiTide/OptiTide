<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<a href="<?= route('admin.chat.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> All chats</a>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= e($conversation['name'] ?: 'Visitor') ?><?= ! empty($conversation['email']) ? ' · ' . e($conversation['email']) : '' ?></span>
                <?= $conversation['mode'] === 'human' ? '<span class="badge text-bg-primary">Human</span>' : '<span class="badge badge-soft">Assistant answering</span>' ?>
            </div>
            <div class="card-body">
                <div class="tk-thread">
                    <?php foreach ($messages as $m): ?>
                        <div class="tk-msg <?= $m['sender'] === 'visitor' ? 'tk-msg--client' : 'tk-msg--staff' ?>">
                            <div class="tk-msg-head">
                                <span class="fw-semibold">
                                    <?php if ($m['sender'] === 'visitor'): ?>Visitor
                                    <?php elseif (! empty($m['is_ai'])): ?>Assistant <span class="badge text-bg-info ms-1">AI</span>
                                    <?php else: ?>You / team<?php endif; ?>
                                </span>
                                <span class="text-muted small"><?= e($m['created_at'] ? date('d M, g:ia', strtotime($m['created_at'])) : '') ?></span>
                            </div>
                            <div class="tk-msg-body"><?= nl2br(e($m['body'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($conversation['status'] !== 'closed'): ?>
            <form method="post" action="<?= route('admin.chat.reply', ['id' => $conversation['id']]) ?>" class="card">
                <?= csrf_field() ?>
                <div class="card-body">
                    <label class="form-label fw-semibold">Reply <span class="text-muted small">(this takes over from the assistant)</span></label>
                    <textarea name="body" rows="3" class="form-control" maxlength="2000" required placeholder="Type your reply…"></textarea>
                </div>
                <div class="card-footer d-flex justify-content-end"><button class="btn btn-brand"><i class="bi bi-send"></i> Send</button></div>
            </form>
        <?php else: ?>
            <div class="alert alert-secondary">This chat is closed.</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Actions</div>
            <div class="card-body d-grid gap-2">
                <?php if ($conversation['mode'] !== 'human'): ?>
                    <form method="post" action="<?= route('admin.chat.takeover', ['id' => $conversation['id']]) ?>"><?= csrf_field() ?><button class="btn btn-outline-brand w-100"><i class="bi bi-person-raised-hand"></i> Take over from assistant</button></form>
                <?php endif; ?>
                <?php if ($conversation['status'] !== 'closed'): ?>
                    <form method="post" action="<?= route('admin.chat.close', ['id' => $conversation['id']]) ?>"><?= csrf_field() ?><button class="btn btn-light w-100">Close chat</button></form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>setTimeout(function(){location.reload();}, 15000);</script>
<?php $this->endSection(); ?>
