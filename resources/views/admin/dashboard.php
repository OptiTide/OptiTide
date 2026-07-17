<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<?php if ($is_fresh): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-rocket-takeoff text-brand"></i> Start Here</div>
        <div class="card-body">
            <p class="text-muted">Nothing has been billed yet. These are the first four steps — the numbers on this page fill in as you go.</p>
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <a href="<?= route('admin.clients.index') ?>" class="btn btn-brand w-100"><i class="bi bi-person-plus"></i> Add a Client</a>
                    <div class="form-text">Everything else hangs off a client record.</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="<?= route('admin.services.index') ?>" class="btn btn-outline-brand w-100"><i class="bi bi-grid"></i> Check the Catalogue</a>
                    <div class="form-text">Confirm your plans and prices are right.</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <?php if (\App\Support\Features::enabled('quotes')): ?>
                        <a href="<?= route('admin.quotes.create') ?>" class="btn btn-outline-brand w-100"><i class="bi bi-file-earmark-text"></i> Send a Quote</a>
                        <div class="form-text">Accepted quotes become invoices.</div>
                    <?php else: ?>
                        <a href="<?= route('admin.invoices.create') ?>" class="btn btn-outline-brand w-100"><i class="bi bi-receipt"></i> Raise an Invoice</a>
                        <div class="form-text">Bill a client directly.</div>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <?php if (\App\Core\Auth::isAdmin()): ?>
                        <a href="<?= route('admin.settings.edit') ?>" class="btn btn-outline-brand w-100"><i class="bi bi-gear"></i> Review Settings</a>
                        <div class="form-text">Company details, payments and features.</div>
                    <?php else: ?>
                        <a href="<?= route('admin.boards.index') ?>" class="btn btn-outline-brand w-100"><i class="bi bi-kanban"></i> Open the Boards</a>
                        <div class="form-text">Track delivery for each service line.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox text-brand"></i> Needs You Today</span>
        <?php if ($actions): ?>
            <span class="badge text-bg-light"><?= count($actions) ?> <?= count($actions) === 1 ? 'item' : 'items' ?></span>
        <?php endif; ?>
    </div>
    <?php if (! $actions): ?>
        <div class="card-body text-center py-4">
            <i class="bi bi-check2-circle fs-3 d-block mb-2 text-success"></i>
            <div class="fw-semibold">You're all caught up</div>
            <p class="text-muted small mb-3">Nothing is waiting on a decision — no overdue invoices, unanswered tickets or pending requests.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Invoice</a>
                <a href="<?= route('admin.clients.create') ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-person-plus"></i> New Client</a>
                <?php if (\App\Support\Features::enabled('quotes')): ?>
                    <a href="<?= route('admin.quotes.create') ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-file-earmark-text"></i> New Quote</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($actions as $action): ?>
                <a href="<?= e($action['url']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                    <span class="badge text-bg-<?= e($action['tone']) ?> rounded-pill" style="min-width:2.5rem"><?= (int) $action['count'] ?></span>
                    <span class="flex-grow-1"><i class="bi <?= e($action['icon']) ?> text-muted me-1"></i> <?= e($action['label']) ?></span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php
    // Outstanding = sent + overdue, so it links to the combined "Unpaid" filter
    // rather than a single status that would under-report it. The revenue cards
    // are payment-dated, which no invoice status filter matches — they jump to
    // the payments table below instead of a list that wouldn't agree with them.
    $cards = [
        ['Active Clients', $stats['clients'], 'bi-people', route('admin.clients.index') . '?status=' . \App\Models\Client::STATUS_ACTIVE],
        ['Revenue This Month', $stats['paid_month']->format(), 'bi-cash-coin', '#payments'],
        ['Revenue This Week', $stats['paid_week']->format(), 'bi-graph-up-arrow', '#payments'],
        ['Outstanding', $stats['outstanding']->format(), 'bi-hourglass-split', route('admin.invoices.index') . '?status=outstanding'],
        ['Overdue Invoices', $stats['overdue'], 'bi-exclamation-triangle', route('admin.invoices.index') . '?status=' . \App\Models\Invoice::STATUS_OVERDUE],
    ];
    foreach ($cards as [$label, $value, $icon, $href]):
        ?>
        <div class="col-6 col-md-4 col-xl">
            <a href="<?= e($href) ?>" class="text-decoration-none text-reset">
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
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">
                                    <i class="bi bi-check2-circle d-block mb-1 opacity-50"></i>
                                    Nobody owes you anything right now.
                                    <div class="small mt-1">Clients with an unpaid invoice are ranked here, biggest balance first.</div>
                                </td>
                            </tr>
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
            <?php if ($top_outstanding): ?>
                <div class="card-footer text-end">
                    <a href="<?= route('admin.invoices.index') ?>?status=outstanding" class="btn btn-sm btn-light">All Unpaid Invoices <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-6" id="payments">
        <div class="card h-100">
            <div class="card-header">Recent Payments</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Client</th><th>Method</th><th>Date</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (! $recent_payments): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-cash-coin d-block mb-1 opacity-50"></i>
                                    No payments taken yet.
                                    <div class="small mt-1">Money lands here when a client pays online, or when you record a payment against an invoice.</div>
                                </td>
                            </tr>
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
            <?php if ($recent_payments): ?>
                <div class="card-footer text-end">
                    <a href="<?= route('admin.invoices.index') ?>?status=<?= \App\Models\Invoice::STATUS_PAID ?>" class="btn btn-sm btn-light">Paid Invoices <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Invoices</span>
        <div class="d-flex gap-2">
            <?php if ($recent): ?>
                <a href="<?= route('admin.invoices.index') ?>" class="btn btn-sm btn-light">View All</a>
            <?php endif; ?>
            <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Invoice</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $recent): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-receipt fs-3 d-block mb-2 opacity-50"></i>
                            No invoices yet.
                            <div class="small mt-1 mb-3">An invoice is how you ask a client for money — raise one here, or accept a quote and it becomes an invoice for you.</div>
                            <a href="<?= route('admin.invoices.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> Create Your First Invoice</a>
                        </td>
                    </tr>
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
