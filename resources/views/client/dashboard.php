<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-value money"><?= e($outstanding->format()) ?></div>
                    <div class="stat-label">Balance owing</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-grid"></i></div>
                <div>
                    <div class="stat-value"><?= e($services) ?></div>
                    <div class="stat-label">Active services</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Recent invoices</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Invoice</th><th>Issued</th><th>Status</th><th class="text-end">Balance</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $recent): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                        <td><?= e($invoice['issue_date']) ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                        <td class="text-end"><a href="<?= route('portal.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
