<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Open Quotes', $stats['open']->format(), 'bi-send'],
        ['Accepted Value', $stats['accepted']->format(), 'bi-check2-circle'],
        ['Draft', $stats['draft_count'], 'bi-file-earmark'],
        ['Accepted', $stats['accepted_count'], 'bi-file-earmark-check'],
    ];
    foreach ($kpis as [$label, $value, $icon]):
        ?>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-value money"><?= e($value) ?></div>
                        <div class="stat-label"><?= e($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <?php // flex-wrap: the filter row is wider than a phone and would push the page sideways. ?>
    <div class="btn-group btn-group-sm flex-wrap">
        <?php $carry = $search !== '' ? '&q=' . urlencode($search) : ''; ?>
        <a href="<?= route('admin.quotes.index') ?><?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" class="btn <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (\App\Models\Quote::STATUSES as $key => $label): ?>
            <a href="<?= route('admin.quotes.index') ?>?status=<?= $key ?><?= e($carry) ?>" class="btn <?= $status === $key ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="get" action="<?= route('admin.quotes.index') ?>" class="d-flex" role="search">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm">
                <input type="search" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Search number or client" aria-label="Search Quotes">
                <button type="submit" class="btn btn-outline-brand"><i class="bi bi-search"></i></button>
                <?php if ($search !== ''): ?>
                    <a href="<?= route('admin.quotes.index') ?><?= $status !== '' ? '?status=' . urlencode($status) : '' ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
        <a href="<?= route('admin.quotes.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Quote</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Quote</th><th>Client</th><th>Issued</th><th>Expires</th><th>Status</th><th class="text-end">Total</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $result['data']): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-file-earmark-text fs-3 d-block mb-2 opacity-50"></i>
                            <?php if ($status !== '' || $search !== ''): ?>
                                No quotes match this filter.
                                <div class="mt-2"><a href="<?= route('admin.quotes.index') ?>" class="btn btn-sm btn-light">Show All Quotes</a></div>
                            <?php else: ?>
                                No quotes yet.
                                <div class="small mt-1 mb-3">A quote is a price you send a client before the work starts. They accept it from a link — no login needed — and it turns into an invoice by itself.</div>
                                <a href="<?= route('admin.quotes.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> Create Your First Quote</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($result['data'] as $quote): ?>
                    <?php $display = \App\Models\Quote::displayStatus($quote); ?>
                    <tr>
                        <td class="fw-semibold"><?= e($quote['number']) ?></td>
                        <td><?= e($client_names[$quote['client_id']] ?? '—') ?></td>
                        <td><?= e($quote['issue_date']) ?></td>
                        <td><?= e($quote['expires_at'] ?: '—') ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Quote::STATUS_COLORS[$display] ?>"><?= e(\App\Models\Quote::STATUSES[$display]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Quote::total($quote)->format()) ?></td>
                        <td class="text-end"><a href="<?= route('admin.quotes.show', ['id' => $quote['id']]) ?>" class="btn btn-sm btn-light">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($result['last_page'] > 1): ?>
    <nav class="mt-3 d-flex justify-content-between">
        <span class="text-muted small">Page <?= $result['current_page'] ?> of <?= $result['last_page'] ?> · <?= $result['total'] ?> quotes</span>
        <div class="btn-group btn-group-sm">
            <?php $q = ($status ? '&status=' . urlencode($status) : '') . ($search !== '' ? '&q=' . urlencode($search) : ''); ?>
            <a class="btn btn-outline-secondary <?= $result['current_page'] <= 1 ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] - 1 ?><?= e($q) ?>">Previous</a>
            <a class="btn btn-outline-secondary <?= $result['current_page'] >= $result['last_page'] ? 'disabled' : '' ?>" href="?page=<?= $result['current_page'] + 1 ?><?= e($q) ?>">Next</a>
        </div>
    </nav>
<?php endif; ?>
<?php $this->endSection(); ?>
