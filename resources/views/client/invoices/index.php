<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="stat-value money"><?= e($outstanding->format()) ?></div><div class="stat-label">Balance Owing</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                <div><div class="stat-value money"><?= e($paid->format()) ?></div><div class="stat-label">Paid to Date</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                <div><div class="stat-value"><?= e($count) ?></div><div class="stat-label">Total Invoices</div></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= route('portal.invoices.index') ?>" class="btn <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (['sent' => 'Unpaid', 'overdue' => 'Overdue', 'paid' => 'Paid'] as $key => $label): ?>
            <a href="<?= route('portal.invoices.index') ?>?status=<?= $key ?>" class="btn <?= $status === $key ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Invoice</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $invoices): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>
                <?php endif; ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                        <td><?= e($invoice['issue_date']) ?></td>
                        <td><?= e($invoice['due_date']) ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::total($invoice)->format()) ?></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= route('portal.invoices.pdf', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light" title="Download PDF"><i class="bi bi-download"></i></a>
                            <a href="<?= route('portal.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm <?= \App\Models\Invoice::isPayable($invoice) ? 'btn-brand' : 'btn-light' ?>"><?= \App\Models\Invoice::isPayable($invoice) ? 'Pay' : 'View' ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
