<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">Follow your project in real time. This is exactly where your work sits with our team right now.</p>

<?php if ($grouped === []): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        No project cards yet. When you place an order, work is created here automatically.
        <div class="mt-3"><a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand">Order a Service</a></div>
    </div></div>
<?php endif; ?>

<?php foreach ($grouped as $group): ?>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-kanban text-brand"></i> <?= e($group['board']) ?></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Task</th><th>Status</th><th>Due</th></tr></thead>
                <tbody>
                    <?php foreach ($group['cards'] as $card): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($card['title']) ?></td>
                            <td><span class="badge badge-soft"><?= e($card['_status']) ?></span></td>
                            <td class="text-nowrap text-muted small"><?= $card['due_date'] ? e(date('d M Y', strtotime($card['due_date']))) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php $this->endSection(); ?>
