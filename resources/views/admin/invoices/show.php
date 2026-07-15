<?php
$this->extends('layouts.admin');
$editable = ! in_array($invoice['status'], [\App\Models\Invoice::STATUS_PAID, \App\Models\Invoice::STATUS_VOID], true);
$payUrl = url('pay/' . $invoice['public_token']);
?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?> mb-1"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span>
        <div class="text-muted small"><?= e($client['business_name'] ?? '') ?> · Due <?= e($invoice['due_date']) ?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($editable): ?>
            <a href="<?= route('admin.invoices.edit', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="<?= route('admin.invoices.pdf', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-download"></i> PDF</a>
        <?php if ($invoice['status'] !== \App\Models\Invoice::STATUS_VOID): ?>
            <form method="post" action="<?= route('admin.invoices.send', ['id' => $invoice['id']]) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-brand"><i class="bi bi-envelope"></i> <?= $invoice['status'] === \App\Models\Invoice::STATUS_DRAFT ? 'Send' : 'Resend' ?></button>
            </form>
        <?php endif; ?>
        <?php if ($editable): ?>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash"></i> Record Payment</button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="text-muted small text-uppercase">Invoice</div>
                        <div class="h4 mb-0"><?= e($invoice['number']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small text-uppercase">Balance due</div>
                        <div class="h4 mb-0 text-brand money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></div>
                    </div>
                </div>

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

                <div class="row justify-content-end">
                    <div class="col-sm-6">
                        <table class="table table-sm">
                            <tr><td class="text-muted">Subtotal (ex GST)</td><td class="text-end money"><?= e(money((int) $invoice['subtotal_cents'], $invoice['currency'])->format()) ?></td></tr>
                            <?php if ((int) $invoice['gst_cents'] > 0): ?>
                                <tr><td class="text-muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="text-end money"><?= e(money((int) $invoice['gst_cents'], $invoice['currency'])->format()) ?></td></tr>
                            <?php endif; ?>
                            <?php if ((int) ($invoice['late_fee_cents'] ?? 0) > 0): ?>
                                <tr><td class="text-danger">Late fee</td><td class="text-end money text-danger">+<?= e(money((int) $invoice['late_fee_cents'], $invoice['currency'])->format()) ?></td></tr>
                            <?php elseif (($invoice['late_fee_waiver_status'] ?? 'none') === 'waived'): ?>
                                <tr><td class="text-muted"><s>Late fee</s> <span class="badge text-bg-success">waived</span></td><td class="text-end text-muted">—</td></tr>
                            <?php endif; ?>
                            <tr class="fw-bold border-top"><td>Total (inc GST)</td><td class="text-end money"><?= e(\App\Models\Invoice::total($invoice)->format()) ?></td></tr>
                            <tr><td class="text-muted">Paid</td><td class="text-end money"><?= e(\App\Models\Invoice::amountPaid($invoice)->format()) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php $lfStatus = $invoice['late_fee_waiver_status'] ?? 'none'; ?>
        <?php if ((int) ($invoice['late_fee_cents'] ?? 0) > 0): ?>
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-bold"><i class="bi bi-exclamation-triangle text-warning"></i> Late fee applied: <?= e(money((int) $invoice['late_fee_cents'], $invoice['currency'])->format()) ?></div>
                            <div class="small text-muted">Charged automatically when this invoice became overdue.<?= $lfStatus === 'requested' ? ' A staff member has requested this be waived.' : '' ?></div>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if (\App\Core\Auth::isAdmin()): ?>
                                <form method="post" action="<?= route('admin.invoices.latefee.waive', ['id' => $invoice['id']]) ?>" onsubmit="return confirm('Waive and remove this late fee?')" class="d-flex gap-2">
                                    <?= csrf_field() ?>
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)" style="max-width:220px">
                                    <button class="btn btn-sm btn-warning"><i class="bi bi-check2"></i> <?= $lfStatus === 'requested' ? 'Approve waiver' : 'Waive late fee' ?></button>
                                </form>
                            <?php elseif ($lfStatus === 'none'): ?>
                                <form method="post" action="<?= route('admin.invoices.latefee.request', ['id' => $invoice['id']]) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary">Request waiver</button>
                                </form>
                            <?php elseif ($lfStatus === 'requested'): ?>
                                <span class="badge text-bg-info align-self-center">Waiver requested — awaiting admin approval</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Payments</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <?php if (! $payments): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No payments recorded.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= e($payment['paid_at']) ?></td>
                                <td><?= e(\App\Models\Payment::METHODS[$payment['method']] ?? $payment['method']) ?></td>
                                <td><?= e($payment['reference'] ?: '—') ?></td>
                                <td class="text-end money"><?= e(money((int) $payment['amount_cents'], $payment['currency'])->format()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Client</div>
            <div class="card-body">
                <div class="fw-semibold"><?= e($client['business_name'] ?? '') ?></div>
                <?php if (! empty($client['email'])): ?><div class="small"><?= e($client['email']) ?></div><?php endif; ?>
                <a href="<?= route('admin.clients.show', ['id' => $invoice['client_id']]) ?>" class="btn btn-sm btn-light mt-2">Open Client</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Public Pay Link</div>
            <div class="card-body">
                <input type="text" class="form-control form-control-sm" value="<?= e($payUrl) ?>" readonly onclick="this.select()">
                <div class="form-text">Share this so the client can pay without logging in.</div>
            </div>
        </div>

        <?php if ($editable): ?>
            <div class="card border-danger-subtle">
                <div class="card-body d-flex gap-2">
                    <form method="post" action="<?= route('admin.invoices.void', ['id' => $invoice['id']]) ?>" onsubmit="return confirm('Void this invoice?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-secondary">Void</button>
                    </form>
                    <?php if (\App\Core\Auth::isAdmin()): ?>
                        <form method="post" action="<?= route('admin.invoices.destroy', ['id' => $invoice['id']]) ?>" onsubmit="return confirm('Permanently delete this invoice?')">
                            <?= csrf_field() ?><?= method_field('DELETE') ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Record payment modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= route('admin.invoices.payments.store', ['id' => $invoice['id']]) ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Amount (<?= e($invoice['currency']) ?>)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?= e(number_format(\App\Models\Invoice::balance($invoice)->dollars(), 2, '.', '')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Method</label>
                    <select name="method" class="form-select" required>
                        <?php foreach ($methods as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" placeholder="Bank ref">
                    </div>
                    <div class="col">
                        <label class="form-label">Paid on</label>
                        <input type="date" name="paid_at" class="form-control" value="<?= e(today()) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand">Record Payment</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
