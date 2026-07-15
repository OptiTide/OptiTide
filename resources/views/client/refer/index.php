<?php
$this->extends('layouts.portal');
$badge = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-info', 'paid' => 'text-bg-success'];
?>
<?php $this->section('content'); ?>

<div class="card border-0 mb-4" style="background:var(--brand-soft)">
    <div class="card-body">
        <div class="h5 fw-bold mb-1"><i class="bi bi-gift"></i> Refer &amp; earn <?= e($ratePercent) ?>%</div>
        <p class="text-muted mb-3">Share your link. When someone you refer signs up and pays their first invoice, you earn <?= e($ratePercent) ?>% of that order as a commission.</p>
        <label class="form-label small fw-semibold">Your referral link</label>
        <div class="input-group">
            <input type="text" class="form-control" id="refLink" value="<?= e($link) ?>" readonly>
            <button class="btn btn-brand" type="button" onclick="navigator.clipboard.writeText(document.getElementById('refLink').value);this.innerHTML='<i class=\'bi bi-check\'></i> Copied'">
                <i class="bi bi-clipboard"></i> Copy
            </button>
        </div>
        <div class="text-muted small mt-1">Referral code: <strong><?= e($code) ?></strong></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value"><?= count($referrals) ?></div><div class="stat-label">Referrals</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money"><?= e($summary['pending']->format()) ?></div><div class="stat-label">Pending</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money"><?= e($summary['approved']->format()) ?></div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value money text-success"><?= e($summary['paid']->format()) ?></div><div class="stat-label">Paid to you</div></div></div></div>
</div>

<div class="card">
    <div class="card-header">Your commissions</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Amount</th><th>Rate</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($commissions as $c): ?>
                    <tr>
                        <td class="text-muted small"><?= e($c['created_at'] ? date('d M Y', strtotime($c['created_at'])) : '—') ?></td>
                        <td class="fw-semibold money"><?= e(money((int) $c['amount_cents'], $c['currency'])->format()) ?></td>
                        <td class="text-muted"><?= e(number_format((int) $c['rate_bps'] / 100, 2)) ?>%</td>
                        <td><span class="badge <?= $badge[$c['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Commission::STATUSES[$c['status']] ?? ucfirst($c['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($commissions === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No commissions yet — share your link to get started.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
