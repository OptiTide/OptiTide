<?php
$this->extends('layouts.public');
$company = config('company');
$display = \App\Models\Quote::displayStatus($quote);
$acceptable = \App\Models\Quote::isAcceptable($quote);
?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h1 class="h3 mb-1">Quote <?= e($quote['number']) ?></h1>
                <span class="badge text-bg-<?= \App\Models\Quote::STATUS_COLORS[$display] ?>"><?= e(\App\Models\Quote::STATUSES[$display]) ?></span>
            </div>
            <a href="<?= route('quote.pdf', ['token' => $quote['public_token']]) ?>" class="btn btn-outline-brand"><i class="bi bi-download"></i> Download PDF</a>
        </div>

        <?php if ($invoice): ?>
            <div class="card border-success mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-bold"><i class="bi bi-check-circle text-success"></i> This quote was accepted<?= $quote['accepted_at'] ? ' on ' . e(substr((string) $quote['accepted_at'], 0, 10)) : '' ?>.</div>
                        <div class="small text-muted">Invoice <?= e($invoice['number']) ?> for <?= e(\App\Models\Invoice::total($invoice)->format()) ?> is ready — it's due <?= e($invoice['due_date']) ?>.</div>
                    </div>
                    <?php // The visitor holds a quote token, not a session — send them to the invoice's own public link. ?>
                    <a href="<?= url('pay/' . $invoice['public_token']) ?>" class="btn btn-brand"><i class="bi bi-receipt"></i> View Invoice <?= e($invoice['number']) ?></a>
                </div>
            </div>
        <?php elseif ($quote['status'] === \App\Models\Quote::STATUS_DECLINED): ?>
            <div class="alert alert-secondary">
                <i class="bi bi-x-circle"></i> This quote was declined<?= $quote['declined_at'] ? ' on ' . e(substr((string) $quote['declined_at'], 0, 10)) : '' ?>. Get in touch any time if you'd like it revisited.
            </div>
        <?php elseif ($display === \App\Models\Quote::STATUS_EXPIRED): ?>
            <div class="alert alert-warning">
                <i class="bi bi-clock-history"></i> This quote expired on <?= e($quote['expires_at']) ?>. Get in touch and we'll prepare an updated one.
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small text-uppercase">From</div>
                        <div class="fw-semibold"><?= e($company['legal_name']) ?></div>
                        <?php if ($company['abn']): ?><div class="small text-muted">ABN <?= e($company['abn']) ?></div><?php endif; ?>
                        <div class="small text-muted"><?= e($company['email']) ?></div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="text-muted small text-uppercase">Prepared for</div>
                        <div class="fw-semibold"><?= e($client['business_name'] ?? '') ?></div>
                        <div class="small text-muted">
                            Issued <?= e($quote['issue_date']) ?><?= $quote['expires_at'] ? ' · Valid until ' . e($quote['expires_at']) : '' ?>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
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
                </div>

                <div class="row justify-content-end">
                    <div class="col-sm-6 col-lg-5">
                        <table class="table table-sm">
                            <?php $this->insert('partials.quote-discount-rows', ['quote' => $quote]); ?>
                            <tr><td class="text-muted">Subtotal (ex GST)</td><td class="text-end money"><?= e(money((int) $quote['subtotal_cents'], $quote['currency'])->format()) ?></td></tr>
                            <?php if ((int) $quote['gst_cents'] > 0): ?>
                                <tr><td class="text-muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="text-end money"><?= e(money((int) $quote['gst_cents'], $quote['currency'])->format()) ?></td></tr>
                            <?php endif; ?>
                            <tr class="fw-bold border-top text-brand"><td>Total (inc GST)</td><td class="text-end money"><?= e(\App\Models\Quote::total($quote)->format()) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (! empty($quote['terms'])): ?>
            <div class="card mb-3">
                <div class="card-header">Terms</div>
                <div class="card-body text-muted small" style="white-space:pre-wrap"><?= e($quote['terms']) ?></div>
            </div>
        <?php endif; ?>

        <?php if (! empty($quote['notes'])): ?>
            <div class="card mb-3">
                <div class="card-body text-muted small" style="white-space:pre-wrap"><?= e($quote['notes']) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($acceptable): ?>
            <div class="card">
                <div class="card-header">Ready to Go Ahead?</div>
                <div class="card-body">
                    <p class="text-muted">Accepting this quote raises the invoice so we can get started. The invoice will appear here straight away.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="<?= route('quote.accept', ['token' => $quote['public_token']]) ?>" onsubmit="return confirm('Accept this quote and raise the invoice?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-brand"><i class="bi bi-check2-circle"></i> Accept Quote</button>
                        </form>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#declineModal">Decline</button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="declineModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" action="<?= route('quote.decline', ['token' => $quote['public_token']]) ?>" class="modal-content">
                        <?= csrf_field() ?>
                        <div class="modal-header"><h5 class="modal-title">Decline Quote</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label class="form-label">Reason (optional)</label>
                            <textarea name="reason" rows="3" class="form-control" placeholder="Anything you'd like us to know?"></textarea>
                            <div class="form-text">This helps us put a better proposal together for you.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-outline-danger">Decline Quote</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>
