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
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4">No boards yet. Run the seeder to create them.</div></div></div>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
