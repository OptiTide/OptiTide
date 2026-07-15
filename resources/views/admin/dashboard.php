<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Active Clients', $stats['clients'], 'bi-people', route('admin.clients.index')],
        ['Outstanding', $stats['outstanding']->format(), 'bi-hourglass-split', route('admin.invoices.index')],
        ['Overdue Invoices', $stats['overdue'], 'bi-exclamation-triangle', route('admin.invoices.index')],
        ['Paid This Month', $stats['paid_month']->format(), 'bi-cash-coin', route('admin.invoices.index')],
    ];
    foreach ($cards as [$label, $value, $icon, $href]):
        ?>
        <div class="col-6 col-xl-3">
            <a href="<?= $href ?>" class="text-decoration-none text-reset">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                        <div>
                            <div class="stat-value money"><?= e($value) ?></div>
                            <div class="stat-label"><?= e($label) ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Invoices</span>
        <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Invoice</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $recent): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No invoices yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                        <td><?= e($client_names[$invoice['client_id']] ?? '—') ?></td>
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
<?php $this->endSection(); ?>
