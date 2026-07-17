<?php
$this->extends('layouts.portal');
$companyEmail = config('company.email');
?>
<?php $this->section('content'); ?>

<div class="card border-0 mb-4" style="background:linear-gradient(120deg,var(--navy-2),var(--navy));color:#fff">
    <div class="card-body">
        <div class="h5 fw-bold mb-1">👋 Welcome to Your Client Portal</div>
        <p class="mb-3" style="color:#cbd2e0">Everything for your project lives here. Not sure where to start? Pick one:</p>
        <div class="row g-2">
            <?php
            $guide = [
                ['bi-bag-plus', 'Order a Service', 'Web, SEO, social or hosting', route('portal.order.index')],
                ['bi-kanban', 'Track Your Project', 'See progress live', route('portal.project')],
                ['bi-receipt', 'View & Pay Invoices', 'PayID, PayPal, Skrill & more', route('portal.invoices.index')],
                ['bi-calendar-event', 'Meetings', 'Book or join a call', route('portal.meetings')],
                // CommissionService pays ONE acquisition commission per referred
                // client, on their first paid invoice — not on every referral.
                ['bi-gift', 'Refer & Earn', 'Earn a % when they first pay', route('portal.refer')],
                ['bi-life-preserver', 'Get Help', 'Support or live chat', route('portal.support.index')],
            ];
            foreach ($guide as [$icon, $t, $d, $href]):
            ?>
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= $href ?>" class="d-flex align-items-start gap-2 p-2 rounded text-decoration-none h-100" style="background:rgba(255,255,255,.07);color:#fff">
                        <i class="bi <?= $icon ?>" style="color:var(--brand);font-size:1.1rem"></i>
                        <span><strong><?= e($t) ?></strong><span class="d-block small" style="color:#b9c0d4"><?= e($d) ?></span></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Balance Owing', $outstanding->format(), 'bi-hourglass-split', route('portal.invoices.index')],
        ['Paid to Date', $paid->format(), 'bi-check2-circle', route('portal.invoices.index')],
        ['Active Services', $services, 'bi-grid', route('portal.services')],
        ['Overdue', $overdue, 'bi-exclamation-triangle', route('portal.invoices.index') . '?status=overdue'],
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

<?php if ($next_due): ?>
    <div class="card mb-4 border-start border-4 <?= $next_due['status'] === 'overdue' ? 'border-danger' : 'border-info' ?>">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="text-muted small text-uppercase"><?= $next_due['status'] === 'overdue' ? 'Payment Overdue' : 'Next Payment Due' ?></div>
                <div class="fw-semibold"><?= e($next_due['number']) ?> · <?= e(\App\Models\Invoice::balance($next_due)->format()) ?> · due <?= e($next_due['due_date']) ?></div>
            </div>
            <a href="<?= route('portal.invoices.show', ['id' => $next_due['id']]) ?>" class="btn btn-brand">Pay Now</a>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Invoices</span>
                <a href="<?= route('portal.invoices.index') ?>" class="btn btn-sm btn-light">View All</a>
            </div>
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
                                <td class="text-end"><a href="<?= route('portal.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm <?= \App\Models\Invoice::isPayable($invoice) ? 'btn-brand' : 'btn-light' ?>"><?= \App\Models\Invoice::isPayable($invoice) ? 'Pay' : 'View' ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Quick Actions</div>
            <div class="list-group list-group-flush">
                <a href="<?= route('portal.invoices.index') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i class="bi bi-receipt text-brand"></i> View &amp; Pay Invoices</a>
                <a href="<?= route('portal.services') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i class="bi bi-grid text-brand"></i> My Services</a>
                <a href="<?= route('portal.profile.edit') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i class="bi bi-person text-brand"></i> Update Profile</a>
                <a href="mailto:<?= e($companyEmail) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2"><i class="bi bi-life-preserver text-brand"></i> Contact Support</a>
            </div>
        </div>

        <?php if ($next_renewal): ?>
            <div class="card">
                <div class="card-header">Next Renewal</div>
                <div class="card-body">
                    <div class="fw-semibold"><?= e($next_renewal['label']) ?></div>
                    <div class="text-muted small">Renews <?= e($next_renewal['next_invoice_date']) ?> · <?= e(money((int) $next_renewal['price_cents'], $next_renewal['currency'])->format()) ?> / <?= e(substr($next_renewal['interval'] ?? 'mo', 0, 2)) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>
