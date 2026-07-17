<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\Discount;
use App\Support\Money;

$isEdit = $discount !== null;
$action = $isEdit ? route('admin.discounts.update', ['id' => $discount['id']]) : route('admin.discounts.store');
$ccy = config('company.currency') ?: 'AUD';

// Cents/basis points in the DB, human numbers in the form.
$amountValue = '';
if ($isEdit) {
    $amountValue = rtrim(rtrim(number_format($discount['value'] / 100, 2, '.', ''), '0'), '.');
}
$dollars = fn (?int $c) => $c === null ? '' : rtrim(rtrim(number_format($c / 100, 2, '.', ''), '0'), '.');
?>

<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?><?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">The Offer</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= e(old('name', $discount['name'] ?? '')) ?>" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" placeholder="e.g. Spring Website Sale" required>
                        <?php if (error('name')): ?><div class="invalid-feedback"><?= e(error('name')) ?></div><?php endif; ?>
                        <div class="form-text">Shown to the client on their invoice, and as the badge on a public sale.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Type</label>
                            <select name="type" id="d_type" class="form-select" onchange="syncType()">
                                <?php foreach (Discount::TYPES as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= old('type', $discount['type'] ?? 'percent') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="d_prefix">%</span>
                                <input type="number" step="0.01" min="0" name="amount" id="d_amount" value="<?= e(old('amount', $amountValue)) ?>" class="form-control <?= has_error('amount') ? 'is-invalid' : '' ?>" placeholder="20" required>
                            </div>
                            <div class="form-text" id="d_hint">A percentage off the price. Capped at 100%.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">How It's Redeemed</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="d_sale" name="is_sale" value="1" onchange="syncSale()" <?= old('is_sale', ! empty($discount['is_sale']) ? '1' : '') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="d_sale"><strong>Run as a public sale</strong> — applies automatically, no code, and shows struck-through pricing on your website</label>
                    </div>
                    <div class="mb-0" id="d_code_wrap">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" value="<?= e(old('code', $discount['code'] ?? '')) ?>" class="form-control text-uppercase" placeholder="SPRING20" maxlength="40">
                        <div class="form-text">What the client types at checkout. Leave blank and we'll make one from the name. Not case-sensitive.</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">What It Applies To</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select name="scope" id="d_scope" class="form-select" onchange="syncScope()">
                            <?php foreach (Discount::SCOPES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= old('scope', $discount['scope'] ?? 'all') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="d_cat_wrap">
                        <label class="form-label">Service line</label>
                        <select name="category_id" class="form-select">
                            <option value="">— choose —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (string) old('category_id', $discount['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0 d-none" id="d_svc_wrap">
                        <label class="form-label">Package</label>
                        <select name="service_id" class="form-select">
                            <option value="">— choose —</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= (string) old('service_id', $discount['service_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?> — <?= e((new Money((int) $s['price_cents'], $s['currency'] ?: $ccy))->format()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">Limits</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="d_active" name="active" value="1" <?= old('active', $isEdit ? ($discount['active'] ? '1' : '') : '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="d_active">Active</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small">Starts</label>
                            <input type="date" name="starts_at" value="<?= e(old('starts_at', substr((string) ($discount['starts_at'] ?? ''), 0, 10))) ?>" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Ends</label>
                            <input type="date" name="ends_at" value="<?= e(old('ends_at', substr((string) ($discount['ends_at'] ?? ''), 0, 10))) ?>" class="form-control">
                            <div class="form-text">Runs all day on the end date.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Max uses (total)</label>
                            <input type="number" min="1" name="max_uses" value="<?= e(old('max_uses', (string) ($discount['max_uses'] ?? ''))) ?>" class="form-control" placeholder="unlimited">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Max per client</label>
                            <input type="number" min="1" name="max_uses_per_client" value="<?= e(old('max_uses_per_client', (string) ($discount['max_uses_per_client'] ?? ''))) ?>" class="form-control" placeholder="unlimited">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Minimum spend</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="min_spend" value="<?= e(old('min_spend', $dollars($discount['min_spend_cents'] ?? null))) ?>" class="form-control" placeholder="none">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Only for one client</label>
                            <select name="client_id" class="form-select">
                                <option value="">Any client</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (string) old('client_id', $discount['client_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Use for a one-off deal. A client-specific discount never shows as a public sale.</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isEdit): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Redemptions</span>
                        <span class="badge text-bg-secondary"><?= e((new Money((int) $given, $ccy))->format()) ?> given away</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($redemptions): ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th class="ps-3">When</th><th>Client</th><th class="text-end pe-3">Amount</th></tr></thead>
                                    <tbody>
                                        <?php foreach (array_slice($redemptions, 0, 10) as $r): ?>
                                            <tr>
                                                <td class="ps-3 small text-nowrap"><?= e(date('j M y', strtotime((string) $r['created_at']))) ?></td>
                                                <td class="small"><?= e(\App\Models\Client::find($r['client_id'])['business_name'] ?? '—') ?></td>
                                                <td class="text-end pe-3 small"><?= e((new Money((int) $r['amount_cents'], $r['currency'] ?: $ccy))->format()) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0 p-3">Not used yet — nobody has redeemed this code. Redemptions are recorded automatically at checkout.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-1 d-flex gap-2">
        <button class="btn btn-brand btn-lg"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save Discount' : 'Create Discount' ?></button>
        <a href="<?= route('admin.discounts.index') ?>" class="btn btn-lg btn-link">Cancel</a>
    </div>
</form>

<script>
function syncType() {
    var t = document.getElementById('d_type').value;
    document.getElementById('d_prefix').textContent = t === 'percent' ? '%' : '$';
    document.getElementById('d_hint').textContent = t === 'percent'
        ? 'A percentage off the price. Capped at 100%.'
        : 'A fixed amount off. Never more than the price itself.';
}
function syncScope() {
    var s = document.getElementById('d_scope').value;
    document.getElementById('d_cat_wrap').classList.toggle('d-none', s !== 'category');
    document.getElementById('d_svc_wrap').classList.toggle('d-none', s !== 'service');
}
function syncSale() {
    // A sale needs no code — hide the field rather than let it look required.
    document.getElementById('d_code_wrap').classList.toggle('d-none', document.getElementById('d_sale').checked);
}
syncType(); syncScope(); syncSale();
</script>
<?php $this->endSection(); ?>
