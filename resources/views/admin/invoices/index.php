<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Outstanding', $stats['outstanding']->format(), 'bi-hourglass-split'],
        ['Paid This Month', $stats['paid_this_month']->format(), 'bi-cash-coin'],
        ['Draft', $stats['draft_count'], 'bi-file-earmark'],
        ['Overdue', $stats['overdue_count'], 'bi-exclamation-triangle'],
    ];
    foreach ($kpis as [$label, $value, $icon]):
        ?>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-value money"><?= e($value) ?></div>
                        <div class="stat-label"><?= e($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <?php $carry = $search !== '' ? '&q=' . urlencode($search) : ''; ?>
        <a href="<?= route('admin.invoices.index') ?><?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" class="btn <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (\App\Models\Invoice::STATUSES as $key => $label): ?>
            <a href="<?= route('admin.invoices.index') ?>?status=<?= $key ?><?= e($carry) ?>" class="btn <?= $status === $key ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="get" action="<?= route('admin.invoices.index') ?>" class="d-flex" role="search">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm">
                <input type="search" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search Number Or Client" aria-label="Search Invoices">
                <button type="submit" class="btn btn-outline-brand"><i class="bi bi-search"></i></button>
                <?php if ($search !== ''): ?>
                    <a href="<?= route('admin.invoices.index') ?><?= $status !== '' ? '?status=' . urlencode($status) : '' ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
        <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Invoice</a>
    </div>
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
            <?php $q = ($status ? '&status=' . urlencode($status) : '') . ($search !== '' ? '&q=' . urlencode($search) : ''); ?>
            <a class="btn btn-outline-secondary <?= $result['current_page'] <= 1 ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] - 1 ?><?= e($q) ?>">Previous</a>
            <a class="btn btn-outline-secondary <?= $result['current_page'] >= $result['last_page'] ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] + 1 ?><?= e($q) ?>">Next</a>
        </div>
    </nav>
<?php endif; ?>
<?php $this->endSection(); ?>
