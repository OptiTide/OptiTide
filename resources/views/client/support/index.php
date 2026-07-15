<?php
$this->extends('layouts.portal');
$badge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Need a hand? Log a request and our team will help.</div>
    <a href="<?= route('portal.support.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Request</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Ref</th><th>Subject</th><th>Category</th><th>Status</th><th>Last Update</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr onclick="window.location='<?= route('portal.support.show', ['id' => $t['id']]) ?>'" style="cursor:pointer">
                        <td class="text-muted small"><?= e($t['number']) ?></td>
                        <td class="fw-semibold"><?= e($t['subject']) ?></td>
                        <td><?= $t['category'] ? '<span class="badge badge-soft">' . e($t['category']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge <?= $badge[$t['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::CLIENT_STATUSES[$t['status']] ?? ucfirst($t['status'])) ?></span></td>
                        <td class="text-nowrap text-muted small"><?= e($t['last_reply_at'] ? date('d M Y', strtotime($t['last_reply_at'])) : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($tickets === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No support requests yet. <a href="<?= route('portal.support.create') ?>">Create one</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
