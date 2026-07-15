<?php
$this->extends('layouts.admin');
$isEdit = $invoice !== null;
$action = $isEdit ? route('admin.invoices.update', ['id' => $invoice['id']]) : route('admin.invoices.store');
$servicesJson = [];
foreach ($services as $s) {
    $servicesJson[$s['id']] = ['name' => $s['name'], 'price' => number_format($s['price_cents'] / 100, 2, '.', '')];
}
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select name="client_id" class="form-select <?= has_error('client_id') ? 'is-invalid' : '' ?>" required>
                                <option value="">Select a client…</option>
                                <?php $selectedClient = old('client_id', $invoice['client_id'] ?? ($preselect ?? '')); ?>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (string) $selectedClient === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (error('client_id')): ?><div class="invalid-feedback"><?= e(error('client_id')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" value="<?= e(old('issue_date', $invoice['issue_date'] ?? today())) ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" value="<?= e(old('due_date', $invoice['due_date'] ?? date('Y-m-d', strtotime('+14 days')))) ?>" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Line Items</span>
                    <button type="button" class="btn btn-sm btn-outline-brand" id="addRow"><i class="bi bi-plus-lg"></i> Add Line</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="itemsTable">
                        <thead>
                            <tr><th style="width:22%">Service</th><th>Description</th><th style="width:90px">Qty</th><th style="width:130px">Unit Price</th><th style="width:40px"></th></tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php
                            $rows = $isEdit ? $items : [[]];
                            foreach ($rows as $i => $item):
                                ?>
                                <tr class="item-row">
                                    <td>
                                        <select name="items[<?= $i ?>][service_id]" class="form-select form-select-sm svc">
                                            <option value="">—</option>
                                            <?php foreach ($services as $s): ?>
                                                <option value="<?= $s['id'] ?>" <?= (string) ($item['service_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[<?= $i ?>][description]" value="<?= e($item['description'] ?? '') ?>" class="form-control form-control-sm desc" placeholder="Description"></td>
                                    <td><input type="number" name="items[<?= $i ?>][quantity]" value="<?= e($item['quantity'] ?? 1) ?>" min="1" class="form-control form-control-sm qty"></td>
                                    <td><input type="number" step="0.01" name="items[<?= $i ?>][unit_price]" value="<?= e(isset($item['unit_price_cents']) ? number_format($item['unit_price_cents'] / 100, 2, '.', '') : '') ?>" class="form-control form-control-sm price" placeholder="0.00"></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger removeRow"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">Notes (shown on the invoice)</label>
                    <textarea name="notes" rows="2" class="form-control"><?= e(old('notes', $invoice['notes'] ?? '')) ?></textarea>
                    <label class="form-label mt-3">Payoneer Payment Link (optional)</label>
                    <input type="url" name="payoneer_link" value="<?= e(old('payoneer_link', $invoice['payoneer_link'] ?? '')) ?>" class="form-control <?= has_error('payoneer_link') ? 'is-invalid' : '' ?>" placeholder="https://payoneer.com/request/…">
                    <?php if (error('payoneer_link')): ?><div class="invalid-feedback"><?= e(error('payoneer_link')) ?></div><?php endif; ?>
                    <div class="form-text">Paste the Payoneer "request a payment" link so the client sees a Pay button.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card position-sticky" style="top:1rem">
                <div class="card-header">Summary</div>
                <div class="card-body">
                    <table class="table table-sm mb-3">
                        <tr><td class="text-muted">Subtotal (ex GST)</td><td class="text-end money" id="sumSub">$0.00</td></tr>
                        <tr><td class="text-muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="text-end money" id="sumGst">$0.00</td></tr>
                        <tr class="fw-bold border-top"><td>Total (inc GST)</td><td class="text-end money" id="sumTotal">$0.00</td></tr>
                    </table>
                    <button type="submit" name="action" value="save" class="btn btn-outline-brand w-100 mb-2">Save as Draft</button>
                    <button type="submit" name="action" value="save_send" class="btn btn-brand w-100">Save &amp; Email Invoice</button>
                    <a href="<?= route('admin.invoices.index') ?>" class="btn btn-link w-100 mt-1">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const SERVICES = <?= json_encode($servicesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const GST_BPS = <?= (int) \App\Support\Gst::basisPoints() ?>;
let rowIndex = <?= $isEdit ? count($items) : 1 ?>;

function fmt(n) { return '$' + n.toLocaleString('en-AU', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

function recompute() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const price = parseFloat(row.querySelector('.price').value) || 0;
        total += qty * price;
    });
    const gst = GST_BPS > 0 ? total * GST_BPS / (10000 + GST_BPS) : 0;
    document.getElementById('sumTotal').textContent = fmt(total);
    document.getElementById('sumGst').textContent = fmt(gst);
    document.getElementById('sumSub').textContent = fmt(total - gst);
}

function wireRow(row) {
    row.querySelector('.svc').addEventListener('change', e => {
        const s = SERVICES[e.target.value];
        if (s) {
            if (!row.querySelector('.desc').value) row.querySelector('.desc').value = s.name;
            row.querySelector('.price').value = s.price;
            recompute();
        }
    });
    row.querySelectorAll('.qty, .price').forEach(i => i.addEventListener('input', recompute));
    row.querySelector('.removeRow').addEventListener('click', () => {
        if (document.querySelectorAll('.item-row').length > 1) { row.remove(); recompute(); }
    });
}

document.getElementById('addRow').addEventListener('click', () => {
    const body = document.getElementById('itemsBody');
    const tpl = document.querySelector('.item-row').cloneNode(true);
    tpl.querySelectorAll('input, select').forEach(el => {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
        if (el.tagName === 'INPUT') el.value = el.classList.contains('qty') ? 1 : '';
        else el.value = '';
    });
    body.appendChild(tpl);
    wireRow(tpl);
    rowIndex++;
});

document.querySelectorAll('.item-row').forEach(wireRow);
recompute();
</script>
<?php $this->endSection(); ?>
