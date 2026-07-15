<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Active Clients', $stats['clients'], 'bi-people', route('admin.clients.index')],
        ['Revenue This Month', $stats['paid_month']->format(), 'bi-cash-coin', route('admin.invoices.index')],
        ['Revenue This Week', $stats['paid_week']->format(), 'bi-graph-up-arrow', route('admin.invoices.index')],
        ['Outstanding', $stats['outstanding']->format(), 'bi-hourglass-split', route('admin.invoices.index')],
        ['Overdue Invoices', $stats['overdue'], 'bi-exclamation-triangle', route('admin.invoices.index')],
    ];
    foreach ($cards as [$label, $value, $icon, $href]):
        ?>
        <div class="col-6 col-md-4 col-xl">
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

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top Outstanding Clients</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Client</th><th class="text-end">Balance</th></tr>
                    </thead>
                    <tbody>
                        <?php if (! $top_outstanding): ?>
                            <tr><td colspan="2" class="text-center text-muted py-4">No Outstanding Balances.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($top_outstanding as $row): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <a href="<?= route('admin.clients.show', ['id' => $row['client_id']]) ?>" class="text-decoration-none text-reset"><?= e($row['business_name']) ?></a>
                                </td>
                                <td class="text-end money"><?= e($row['balance']->format()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">Recent Payments</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Client</th><th>Method</th><th>Date</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (! $recent_payments): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No Payments Yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($payment['business_name']) ?></td>
                                <?php $ml = ['payid' => 'PayID', 'payoneer' => 'Payoneer', 'manual' => 'Manual'][$payment['method']] ?? ucwords(str_replace('_', ' ', (string) $payment['method'])); ?>
                                <td><span class="badge text-bg-light"><?= e($ml) ?></span></td>
                                <td><?= e($payment['paid_at'] ? date('d M Y', strtotime($payment['paid_at'])) : '—') ?></td>
                                <td class="text-end money"><?= e($payment['amount']->format()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
