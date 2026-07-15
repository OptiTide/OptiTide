<?php
$this->extends('layouts.admin');
$badge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary'];
$prio = ['high' => 'text-bg-danger', 'normal' => 'badge-soft', 'low' => 'text-bg-light'];
$filters = ['' => 'All'] + \App\Models\Ticket::STATUSES;
?>
<?php $this->section('content'); ?>

<div class="btn-group mb-3" role="group">
    <?php foreach ($filters as $key => $label): ?>
        <a href="<?= route('admin.tickets.index') ?><?= $key === '' ? '' : '?status=' . e($key) ?>"
           class="btn btn-sm <?= ($status ?? '') === $key ? 'btn-brand' : 'btn-outline-brand' ?>">
            <?= e($label) ?> <span class="badge text-bg-light ms-1"><?= (int) ($counts[$key] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Ref</th><th>Subject</th><th>Client</th><th>Category</th><th>Priority</th><th>Status</th><th>Last Update</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr onclick="window.location='<?= route('admin.tickets.show', ['id' => $t['id']]) ?>'" style="cursor:pointer">
                        <td class="text-muted small"><?= e($t['number']) ?></td>
                        <td class="fw-semibold"><?= e($t['subject']) ?></td>
                        <td><?= e($t['client_id'] ? ($client_names[$t['client_id']] ?? '—') : '—') ?></td>
                        <td><?= $t['category'] ? e($t['category']) : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge <?= $prio[$t['priority']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::PRIORITIES[$t['priority']] ?? ucfirst($t['priority'])) ?></span></td>
                        <td><span class="badge <?= $badge[$t['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::STATUSES[$t['status']] ?? ucfirst($t['status'])) ?></span></td>
                        <td class="text-nowrap text-muted small"><?= e($t['last_reply_at'] ? date('d M Y', strtotime($t['last_reply_at'])) : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($tickets === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No tickets in this view.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-arrow-clockwise"></i> This page refreshes automatically every couple of minutes.</p>
<script>
// Auto-refresh the helpdesk queue so new tickets/replies appear without a manual reload.
setTimeout(function () { location.reload(); }, 150000);
</script>
<?php $this->endSection(); ?>
