<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= route('admin.invoices.index') ?>" class="btn <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (\App\Models\Invoice::STATUSES as $key => $label): ?>
            <a href="<?= route('admin.invoices.index') ?>?status=<?= $key ?>" class="btn <?= $status === $key ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Invoice</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $result['data']): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No invoices found.</td></tr>
                <?php endif; ?>
                <?php foreach ($result['data'] as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                        <td><?= e($client_names[$invoice['client_id']] ?? '—') ?></td>
                        <td><?= e($invoice['issue_date']) ?></td>
                        <td><?= e($invoice['due_date']) ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::total($invoice)->format()) ?></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                        <td class="text-end"><a href="<?= route('admin.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($result['last_page'] > 1): ?>
    <nav class="mt-3 d-flex justify-content-between">
        <span class="text-muted small">Page <?= $result['current_page'] ?> of <?= $result['last_page'] ?> · <?= $result['total'] ?> invoices</span>
        <div class="btn-group btn-group-sm">
            <?php $q = $status ? '&status=' . urlencode($status) : ''; ?>
            <a class="btn btn-outline-secondary <?= $result['current_page'] <= 1 ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] - 1 ?><?= e($q) ?>">Previous</a>
            <a class="btn btn-outline-secondary <?= $result['current_page'] >= $result['last_page'] ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] + 1 ?><?= e($q) ?>">Next</a>
        </div>
    </nav>
<?php endif; ?>
<?php $this->endSection(); ?>
