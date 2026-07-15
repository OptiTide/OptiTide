<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>
<div class="card">
    <div class="card-header">Your services &amp; subscriptions</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Service</th><th>Billing</th><th class="text-end">Price</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (! $engagements): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No active services.</td></tr>
                <?php endif; ?>
                <?php foreach ($engagements as $e): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($e['label']) ?></td>
                        <td>
                            <?php if ($e['billing_type'] === 'recurring'): ?>
                                <?= e(ucfirst($e['interval'] ?? 'monthly')) ?>
                            <?php else: ?>
                                One-off
                            <?php endif; ?>
                        </td>
                        <td class="text-end money"><?= e(money((int) $e['price_cents'], $e['currency'])->format()) ?><?php if ($e['billing_type'] === 'recurring'): ?><span class="text-muted small">/<?= e(substr($e['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?></td>
                        <td><span class="badge <?= $e['status'] === 'active' ? 'text-bg-success' : 'badge-soft' ?>"><?= e(ucfirst($e['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
