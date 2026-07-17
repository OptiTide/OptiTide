<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">Follow your project in real time. This is exactly where your work sits with our team right now.</p>

<?php if ($grouped === []): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-kanban fs-3 d-block mb-2"></i>
        <div class="fw-semibold text-body">Nothing Under Way Yet</div>
        <p class="mb-3">Once you order a service, every piece of work shows up here — what we're on, what's done, when it's due, and any notes from the team. No need to chase us for an update.</p>
        <a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand"><i class="bi bi-bag-plus"></i> Order a Service</a>
    </div></div>
<?php endif; ?>

<?php foreach ($grouped as $group): ?>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-kanban text-brand"></i> <?= e($group['board']) ?></div>
        <div class="card-body pt-2">
            <?php foreach ($group['cards'] as $card): ?>
                <?php $bar = $card['_progress']; ?>
                <div class="pj-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="fw-semibold"><?= e($card['title']) ?></div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (! empty($card['completed_at'])): ?>
                                <span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> Complete</span>
                            <?php endif; ?>
                            <span class="badge <?= e(\App\Models\BoardCard::priorityBadge($card['priority'] ?? null)) ?>"><?= e(\App\Models\BoardCard::priorityLabel($card['priority'] ?? null)) ?></span>
                            <span class="badge badge-soft"><?= e($card['_status']) ?></span>
                        </div>
                    </div>

                    <div class="text-muted small mt-1">
                        <i class="bi bi-calendar-event"></i>
                        Due <?= $card['due_date'] ? e(date('d M Y', strtotime($card['due_date']))) : 'not scheduled yet' ?>
                    </div>

                    <?php if ($bar['total'] > 0): ?>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div class="progress flex-grow-1" style="height: 6px;" role="progressbar" aria-valuenow="<?= $bar['pct'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progress on <?= e($card['title']) ?>">
                                <div class="progress-bar bg-success" style="width: <?= $bar['pct'] ?>%"></div>
                            </div>
                            <span class="text-muted small text-nowrap"><?= $bar['done'] ?>/<?= $bar['total'] ?> done</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($card['_comments'] !== []): ?>
                        <div class="pj-notes">
                            <?php foreach ($card['_comments'] as $comment): ?>
                                <div class="pj-note">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold small"><?= e(config('company.brand_name')) ?></span>
                                        <span class="text-muted small"><?= e($comment['created_at'] ? date('d M Y', strtotime($comment['created_at'])) : '') ?></span>
                                    </div>
                                    <div class="small"><?= nl2br(e($comment['body'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php $this->endSection(); ?>
