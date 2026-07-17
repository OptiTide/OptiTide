<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-grid"></i></div>
                <div><div class="stat-value"><?= e($active) ?></div><div class="stat-label">Active Services</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div>
                    <div class="stat-value money"><?= e($monthly->format()) ?></div>
                    <div class="stat-label">Approx. Monthly</div>
                    <?php // Yearly engagements are normalised to a monthly figure, so say
                          // "approx." and explain it rather than implying a real invoice. ?>
                    <div class="small text-muted">Your recurring services, per month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0" style="background:var(--brand-soft)">
            <div class="card-body d-flex align-items-center justify-content-between gap-2">
                <div><div class="fw-semibold">Need a Change?</div><div class="text-muted small">Upgrade, pause or add a service.</div></div>
                <a href="<?= route('portal.support.create') ?>" class="btn btn-sm btn-brand">Ask Us</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Your Services &amp; Subscriptions</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <?php // Started is the least useful column on a phone; Next Invoice and
                  // Price are what a client checks here. ?>
            <thead>
                <tr><th>Service</th><th class="d-none d-sm-table-cell">Billing</th><th class="text-end">Price</th><th>Next Invoice</th><th class="d-none d-lg-table-cell">Started</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $engagements): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-grid fs-3 d-block mb-2"></i>
                            <div class="fw-semibold text-body">No Services Yet</div>
                            <p class="mb-3">This is where everything you've bought from us lives — what it costs, when it next bills, and a one-click cancel. Nothing here yet.</p>
                            <a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand"><i class="bi bi-bag-plus"></i> Order a Service</a>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($engagements as $e): ?>
                    <tr id="engagement-<?= (int) $e['id'] ?>">
                        <td class="fw-semibold"><?= e($e['label']) ?><?php if (! empty($e['reference'])): ?><div class="text-muted small"><?= e($e['reference']) ?></div><?php endif; ?></td>
                        <td class="d-none d-sm-table-cell">
                            <?php if ($e['billing_type'] === 'recurring'): ?>
                                <span class="badge badge-soft"><?= e(ucfirst($e['interval'] ?? 'monthly')) ?></span>
                            <?php else: ?>
                                <span class="badge badge-soft">One-off</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end money"><?= e(money((int) $e['price_cents'], $e['currency'])->format()) ?><?php if ($e['billing_type'] === 'recurring'): ?><span class="text-muted small">/<?= e(substr($e['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?></td>
                        <td class="text-nowrap"><?= $e['billing_type'] === 'recurring' && $e['next_invoice_date'] ? e($e['next_invoice_date']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($e['started_at'] ?: '—') ?></td>
                        <td><span class="badge <?= $e['status'] === 'active' ? 'text-bg-success' : 'badge-soft' ?>"><?= e(ucfirst($e['status'])) ?></span></td>
                        <td class="text-end">
                            <?php if ($e['status'] === 'active'): ?>
                                <form method="post" action="<?= route('portal.services.cancel', ['id' => $e['id']]) ?>" onsubmit="return confirm('Cancel this service? You won\'t be billed again for it.')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
