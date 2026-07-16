<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="stat-value money"><?= e($open->format()) ?></div><div class="stat-label">Awaiting Your Decision</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                <div><div class="stat-value money"><?= e($accepted->format()) ?></div><div class="stat-label">Accepted to Date</div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div><div class="stat-value"><?= e($count) ?></div><div class="stat-label">Total Quotes</div></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= route('portal.quotes.index') ?>" class="btn <?= $status === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach (['sent' => 'Open', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired'] as $key => $label): ?>
            <a href="<?= route('portal.quotes.index') ?>?status=<?= $key ?>" class="btn <?= $status === $key ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Quote</th><th>Issued</th><th>Expires</th><th>Status</th><th class="text-end">Total</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $quotes): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No quotes found.</td></tr>
                <?php endif; ?>
                <?php foreach ($quotes as $quote): ?>
                    <?php
                    $display = \App\Models\Quote::displayStatus($quote);
                    $acceptable = \App\Models\Quote::isAcceptable($quote);
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= e($quote['number']) ?></td>
                        <td><?= e($quote['issue_date']) ?></td>
                        <td><?= e($quote['expires_at'] ?: '—') ?></td>
                        <td><span class="badge text-bg-<?= \App\Models\Quote::STATUS_COLORS[$display] ?>"><?= e(\App\Models\Quote::STATUSES[$display]) ?></span></td>
                        <td class="text-end money"><?= e(\App\Models\Quote::total($quote)->format()) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= route('portal.quotes.pdf', ['id' => $quote['id']]) ?>" class="btn btn-sm btn-light" title="Download PDF"><i class="bi bi-download"></i></a>
                            <a href="<?= route('portal.quotes.show', ['id' => $quote['id']]) ?>" class="btn btn-sm <?= $acceptable ? 'btn-brand' : 'btn-light' ?>"><?= $acceptable ? 'Review' : 'View' ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
