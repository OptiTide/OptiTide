<?php
$this->extends('layouts.admin');
$icons = ['web-design' => 'bi-palette', 'seo' => 'bi-graph-up-arrow', 'smm' => 'bi-megaphone'];
?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">A separate delivery board for each service line. Drag cards between columns to track every project from start to finish.</p>

<div class="row g-3">
    <?php foreach ($boards as $b): ?>
        <div class="col-md-4">
            <a href="<?= route('admin.boards.show', ['key' => $b['key']]) ?>" class="card h-100 text-decoration-none board-tile">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi <?= $icons[$b['key']] ?? 'bi-kanban' ?>"></i></div>
                    <div>
                        <div class="fw-bold text-body"><?= e($b['name']) ?></div>
                        <div class="text-muted small"><?= (int) ($counts[$b['id']] ?? 0) ?> card<?= (int) ($counts[$b['id']] ?? 0) === 1 ? '' : 's' ?></div>
                    </div>
                    <i class="bi bi-arrow-right ms-auto text-brand"></i>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
    <?php if ($boards === []): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-kanban fs-3 d-block mb-2 opacity-50"></i>
                    <div class="fw-semibold text-body">No boards are set up</div>
                    <p class="small mb-0">
                        Boards are created during installation, one per service line, and a card is added automatically
                        whenever an order comes in. If none are listed, the install step that creates them hasn't run —
                        boards can't be added from this screen.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
