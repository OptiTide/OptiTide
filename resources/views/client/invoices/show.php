<?php
$this->extends('layouts.portal');
$company = config('company');
$balance = \App\Models\Invoice::balance($invoice);
?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h2 class="h4 mb-1">Invoice <?= e($invoice['number']) ?></h2>
        <span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span>
    </div>
    <a href="<?= route('portal.invoices.pdf', ['id' => $invoice['id']]) ?>" class="btn btn-outline-brand"><i class="bi bi-download"></i> Download PDF</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-sm-6">
                <div class="text-muted small text-uppercase">From</div>
                <div class="fw-semibold"><?= e($company['legal_name']) ?></div>
                <?php if ($company['abn']): ?><div class="small text-muted">ABN <?= e($company['abn']) ?></div><?php endif; ?>
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="text-muted small text-uppercase">Issued / Due</div>
                <div><?= e($invoice['issue_date']) ?> · <?= e($invoice['due_date']) ?></div>
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
                            <td class="text-end money"><?= e(money((int) $item['unit_price_cents'], $invoice['currency'])->format()) ?></td>
                            <td class="text-end money"><?= e(money((int) $item['line_total_cents'], $invoice['currency'])->format()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-sm-6 col-lg-5">
                <table class="table table-sm">
                    <tr><td class="text-muted">Subtotal (ex GST)</td><td class="text-end money"><?= e(money((int) $invoice['subtotal_cents'], $invoice['currency'])->format()) ?></td></tr>
                    <?php if ((int) $invoice['gst_cents'] > 0): ?>
                        <tr><td class="text-muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="text-end money"><?= e(money((int) $invoice['gst_cents'], $invoice['currency'])->format()) ?></td></tr>
                    <?php endif; ?>
                    <?php if ((int) ($invoice['late_fee_cents'] ?? 0) > 0): ?>
                        <tr><td class="text-muted">Late payment fee</td><td class="text-end money">+<?= e(money((int) $invoice['late_fee_cents'], $invoice['currency'])->format()) ?></td></tr>
                    <?php endif; ?>
                    <tr class="fw-bold border-top"><td>Total (inc GST)</td><td class="text-end money"><?= e(\App\Models\Invoice::total($invoice)->format()) ?></td></tr>
                    <?php if ((int) $invoice['amount_paid_cents'] > 0): ?>
                        <tr><td class="text-muted">Paid</td><td class="text-end money">− <?= e(\App\Models\Invoice::amountPaid($invoice)->format()) ?></td></tr>
                    <?php endif; ?>
                    <tr class="fw-bold text-brand"><td>Balance due</td><td class="text-end money"><?= e($balance->format()) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (\App\Models\Invoice::isPaid($invoice)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> This invoice has been paid in full. Thank you!</div>
<?php else: ?>
    <?php $credit = (int) ($client['credit_cents'] ?? 0); if ($credit > 0): ?>
        <div class="card border-0 mb-3" style="background:var(--brand-soft)">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><i class="bi bi-wallet2"></i> <strong>Account credit available:</strong> <span class="money"><?= e(money($credit, $invoice['currency'])->format()) ?></span></div>
                <form method="post" action="<?= route('portal.invoices.credit', ['id' => $invoice['id']]) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-brand">Apply credit to this invoice</button></form>
            </div>
        </div>
    <?php endif; ?>

    <h3 class="h5 mb-3">How to Pay</h3>
    <div class="row g-3">
        <?php foreach ($instructions as $instruction): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="h6 mb-3"><?= e($instruction->label) ?></h4>
                        <?php if ($instruction->type === 'bank_transfer'): ?>
                            <?php if ($instruction->available): ?>
                                <table class="table table-sm mb-2">
                                    <?php foreach ($instruction->details as $label => $value): ?>
                                        <tr><td class="text-muted"><?= e($label) ?></td><td class="text-end fw-semibold"><?= e($value) ?></td></tr>
                                    <?php endforeach; ?>
                                </table>
                                <?php if ($instruction->note): ?><p class="text-muted small mb-0"><?= e($instruction->note) ?></p><?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted small mb-0">Not available yet.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($instruction->available): ?>
                                <a href="<?= e($instruction->actionUrl) ?>" target="_blank" rel="noopener" class="btn btn-brand w-100"><?= e($instruction->actionText ?? 'Pay Now') ?></a>
                                <?php if ($instruction->note): ?><p class="text-muted small mb-0 mt-2"><?= e($instruction->note) ?></p><?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted small mb-0"><?= e($instruction->note) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>
