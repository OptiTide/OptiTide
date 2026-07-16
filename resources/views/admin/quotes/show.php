<?php
$this->extends('layouts.admin');
$display = \App\Models\Quote::displayStatus($quote);
$converted = \App\Models\Quote::isConverted($quote);
$editable = in_array($quote['status'], [\App\Models\Quote::STATUS_DRAFT, \App\Models\Quote::STATUS_SENT], true) && ! $converted;
$publicUrl = url('quote/' . $quote['public_token']);
?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <span class="badge text-bg-<?= \App\Models\Quote::STATUS_COLORS[$display] ?> mb-1"><?= e(\App\Models\Quote::STATUSES[$display]) ?></span>
        <div class="text-muted small"><?= e($client['business_name'] ?? '') ?><?= $quote['expires_at'] ? ' · Expires ' . e($quote['expires_at']) : '' ?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($editable): ?>
            <a href="<?= route('admin.quotes.edit', ['id' => $quote['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="<?= route('admin.quotes.pdf', ['id' => $quote['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-download"></i> PDF</a>
        <?php if (! $converted): ?>
            <form method="post" action="<?= route('admin.quotes.send', ['id' => $quote['id']]) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-brand"><i class="bi bi-envelope"></i> <?= $quote['status'] === \App\Models\Quote::STATUS_DRAFT ? 'Send' : 'Resend' ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($converted && $invoice): ?>
    <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="bi bi-check-circle"></i> Accepted<?= $quote['accepted_at'] ? ' on ' . e(substr((string) $quote['accepted_at'], 0, 10)) : '' ?> and converted to invoice <strong><?= e($invoice['number']) ?></strong>.
        </div>
        <a href="<?= route('admin.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-success">Open Invoice</a>
    </div>
<?php elseif ($quote['status'] === \App\Models\Quote::STATUS_DECLINED): ?>
    <div class="alert alert-danger">
        <i class="bi bi-x-circle"></i> Declined<?= $quote['declined_at'] ? ' on ' . e(substr((string) $quote['declined_at'], 0, 10)) : '' ?>.
        <?php if (! empty($quote['decline_reason'])): ?>
            <div class="small mt-1">Reason: <?= e($quote['decline_reason']) ?></div>
        <?php endif; ?>
    </div>
<?php elseif ($display === \App\Models\Quote::STATUS_EXPIRED): ?>
    <div class="alert alert-secondary">
        <i class="bi bi-clock-history"></i> This quote expired on <?= e($quote['expires_at']) ?> and can no longer be accepted. Edit the expiry date and resend to revive it.
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="text-muted small text-uppercase">Quote</div>
                        <div class="h4 mb-0"><?= e($quote['number']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small text-uppercase">Total</div>
                        <div class="h4 mb-0 text-brand money"><?= e(\App\Models\Quote::total($quote)->format()) ?></div>
                    </div>
                </div>

                <table class="table align-middle">
                    <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e($item['description']) ?></td>
                                <td class="text-end"><?= e($item['quantity']) ?></td>
                                <td class="text-end money"><?= e(money((int) $item['unit_price_cents'], $quote['currency'])->format()) ?></td>
                                <td class="text-end money"><?= e(money((int) $item['line_total_cents'], $quote['currency'])->format()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row justify-content-end">
                    <div class="col-sm-6">
                        <table class="table table-sm">
                            <?php $this->insert('partials.quote-discount-rows', ['quote' => $quote]); ?>
                            <tr><td class="text-muted">Subtotal (ex GST)</td><td class="text-end money"><?= e(money((int) $quote['subtotal_cents'], $quote['currency'])->format()) ?></td></tr>
                            <?php if ((int) $quote['gst_cents'] > 0): ?>
                                <tr><td class="text-muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="text-end money"><?= e(money((int) $quote['gst_cents'], $quote['currency'])->format()) ?></td></tr>
                            <?php endif; ?>
                            <tr class="fw-bold border-top"><td>Total (inc GST)</td><td class="text-end money"><?= e(\App\Models\Quote::total($quote)->format()) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (! empty($quote['terms'])): ?>
            <div class="card mb-3">
                <div class="card-header">Terms</div>
                <div class="card-body text-muted" style="white-space:pre-wrap"><?= e($quote['terms']) ?></div>
            </div>
        <?php endif; ?>

        <?php if (! empty($quote['notes'])): ?>
            <div class="card">
                <div class="card-header">Notes</div>
                <div class="card-body text-muted" style="white-space:pre-wrap"><?= e($quote['notes']) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Client</div>
            <div class="card-body">
                <div class="fw-semibold"><?= e($client['business_name'] ?? '') ?></div>
                <?php if (! empty($client['email'])): ?><div class="small"><?= e($client['email']) ?></div><?php endif; ?>
                <a href="<?= route('admin.clients.show', ['id' => $quote['client_id']]) ?>" class="btn btn-sm btn-light mt-2">Open Client</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Public Accept Link</div>
            <div class="card-body">
                <input type="text" class="form-control form-control-sm" value="<?= e($publicUrl) ?>" readonly onclick="this.select()">
                <div class="form-text">Share this so the client can accept without logging in.</div>
            </div>
        </div>

        <?php if (\App\Core\Auth::isAdmin()): ?>
            <div class="card border-danger-subtle">
                <div class="card-body">
                    <form method="post" action="<?= route('admin.quotes.destroy', ['id' => $quote['id']]) ?>" onsubmit="return confirm('Permanently delete this quote?')">
                        <?= csrf_field() ?><?= method_field('DELETE') ?>
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>
