<?php
$this->extends('layouts.admin');
$badge = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-info', 'paid' => 'text-bg-success'];
?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money"><?= e(money($totals['pending'], $currency)->format()) ?></div><div class="stat-label">Pending</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money"><?= e(money($totals['approved'], $currency)->format()) ?></div><div class="stat-label">Approved (to pay)</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money text-success"><?= e(money($totals['paid'], $currency)->format()) ?></div><div class="stat-label">Paid</div></div></div></div>
</div>

<div class="card">
    <div class="card-header">Referral Commissions</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Date</th><th>Referrer</th><th>Referred Client</th><th>Amount</th><th>Rate</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($commissions as $c): ?>
                    <tr>
                        <td class="text-muted small"><?= e($c['created_at'] ? date('d M Y', strtotime($c['created_at'])) : '—') ?></td>
                        <td class="fw-semibold"><?= e($referrer_names[$c['referrer_id']] ?? ('User #' . $c['referrer_id'])) ?></td>
                        <td><?= e($c['client_id'] ? ($client_names[$c['client_id']] ?? '—') : '—') ?></td>
                        <td class="fw-semibold money"><?= e(money((int) $c['amount_cents'], $c['currency'])->format()) ?></td>
                        <td class="text-muted"><?= e(number_format((int) $c['rate_bps'] / 100, 2)) ?>%</td>
                        <td><span class="badge <?= $badge[$c['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Commission::STATUSES[$c['status']] ?? ucfirst($c['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <?php if ($c['status'] === 'pending'): ?>
                                <form method="post" action="<?= route('admin.commissions.approve', ['id' => $c['id']]) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-brand">Approve</button></form>
                            <?php endif; ?>
                            <?php if ($c['status'] !== 'paid'): ?>
                                <form method="post" action="<?= route('admin.commissions.pay', ['id' => $c['id']]) ?>" class="d-inline" onsubmit="return confirm('Mark this commission as paid?')"><?= csrf_field() ?><button class="btn btn-sm btn-success">Mark Paid</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($commissions === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No commissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
