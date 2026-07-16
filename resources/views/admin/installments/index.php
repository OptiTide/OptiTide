<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<p class="text-muted mb-3">Clients who requested to pay by instalments (or a hardship plan) instead of the default. Approve to issue the split invoices, or decline to issue a single pay-in-full invoice.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Requested</th><th>Client</th><th>Service</th><th>Plan</th><th class="text-end">Order Value</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td class="text-muted small"><?= e($r['created_at'] ? date('d M Y', strtotime($r['created_at'])) : '') ?></td>
                        <td class="fw-semibold"><?= e($client_names[$r['client_id']] ?? ('Client #' . $r['client_id'])) ?></td>
                        <td><?= e($service_names[$r['service_id']] ?? '—') ?></td>
                        <td><span class="badge badge-soft"><?= e($r['plan_key']) ?></span></td>
                        <td class="text-end money"><?= e(money((int) $r['price_cents'], config('company.currency', 'AUD'))->format()) ?></td>
                        <td class="text-end text-nowrap">
                            <form method="post" action="<?= route('admin.installments.approve', ['id' => $r['id']]) ?>" class="d-inline" onsubmit="return confirm('Approve this plan and issue the instalment invoices?')"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
                            <form method="post" action="<?= route('admin.installments.decline', ['id' => $r['id']]) ?>" class="d-inline" onsubmit="return confirm('Decline and issue a pay-in-full invoice instead?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Decline</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($requests === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No pending payment-plan requests.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
