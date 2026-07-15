<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-header">Your invoices</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Balance</th><th></th></tr></thead>
            <tbody>
                <?php if (! $invoices): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No invoices yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                        <td><?= e($invoice['issue_date']) ?></td>
                        <td><?= e($invoice['due_date']) ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                        <td class="text-end">
                            <a href="<?= route('portal.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm <?= \App\Models\Invoice::isPayable($invoice) ? 'btn-brand' : 'btn-light' ?>">
                                <?= \App\Models\Invoice::isPayable($invoice) ? 'Pay' : 'View' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
