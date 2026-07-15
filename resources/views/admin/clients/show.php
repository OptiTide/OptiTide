<?php
$this->extends('layouts.admin');
$currency = config('company.currency', 'AUD');
$servicesJson = [];
foreach ($services as $s) {
    $servicesJson[$s['id']] = ['name' => $s['name'], 'price' => number_format($s['price_cents'] / 100, 2, '.', ''), 'billing' => $s['billing_type'], 'interval' => $s['interval']];
}
?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <span class="badge <?= $client['status'] === 'active' ? 'text-bg-success' : 'badge-soft' ?>"><?= e(ucfirst($client['status'])) ?></span>
        <?php if ($client['email']): ?><span class="text-muted small ms-1"><?= e($client['email']) ?></span><?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= route('admin.invoices.create') ?>?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-brand"><i class="bi bi-receipt"></i> New Invoice</a>
        <a href="<?= route('admin.clients.edit', ['id' => $client['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i> Edit</a>
        <?php if ($client['status'] === 'active'): ?>
            <form method="post" action="<?= route('admin.clients.destroy', ['id' => $client['id']]) ?>" onsubmit="return confirm('Archive this client?')">
                <?= csrf_field() ?><?= method_field('DELETE') ?>
                <button class="btn btn-sm btn-outline-secondary">Archive</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Details</div>
            <div class="card-body">
                <dl class="mb-0 small">
                    <dt class="text-muted">Contact</dt><dd><?= e($client['contact_name'] ?: '—') ?></dd>
                    <dt class="text-muted">Phone</dt><dd><?= e($client['phone'] ?: '—') ?></dd>
                    <dt class="text-muted">ABN</dt><dd><?= e($client['abn'] ?: '—') ?></dd>
                    <dt class="text-muted">Address</dt><dd><?= e(\App\Models\Client::fullAddress($client) ?: '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3" id="services">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Services &amp; Subscriptions</span>
                <button class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#engModal" onclick="engAdd()"><i class="bi bi-plus-lg"></i> Add</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Service</th><th>Billing</th><th class="text-end">Price</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $engagements): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No services yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($engagements as $e): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($e['label']) ?><?php if (! empty($e['reference'])): ?><div class="text-muted small"><?= e($e['reference']) ?></div><?php endif; ?></td>
                                <td>
                                    <?php if ($e['billing_type'] === 'recurring'): ?>
                                        <span class="badge badge-soft"><?= e(ucfirst($e['interval'] ?? 'monthly')) ?></span>
                                        <?php if ($e['next_invoice_date']): ?><div class="small text-muted">next <?= e($e['next_invoice_date']) ?></div><?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-soft">One-off</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end money"><?= e(money((int) $e['price_cents'], $e['currency'])->format()) ?></td>
                                <td><span class="badge <?= $e['status'] === 'active' ? 'text-bg-success' : 'badge-soft' ?>"><?= e(ucfirst($e['status'])) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-link" onclick='engEdit(<?= json_encode($e, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                                    <form method="post" action="<?= route('admin.engagements.destroy', ['id' => $e['id']]) ?>" class="d-inline" onsubmit="return confirm('Remove this service?')">
                                        <?= csrf_field() ?><?= method_field('DELETE') ?>
                                        <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Invoices</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Invoice</th><th>Issued</th><th>Status</th><th class="text-end">Balance</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $invoices): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No invoices yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                                <td><?= e($invoice['issue_date']) ?></td>
                                <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                                <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                                <td class="text-end"><a href="<?= route('admin.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm btn-light">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (! empty($intakes)): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-clipboard-check"></i> Project Briefs</div>
            <div class="card-body">
                <?php foreach ($intakes as $intake): ?>
                    <?php $ans = json_decode((string) $intake['data'], true) ?: []; $qset = \App\Models\ProjectIntake::questionsFor($intake['category']); ?>
                    <div class="mb-3 pb-2 border-bottom">
                        <div class="fw-semibold"><?= e($qset['label'] ?? ucfirst((string) $intake['category'])) ?> <span class="text-muted small"><?= e($intake['reference'] ?? '') ?></span></div>
                        <dl class="row small mb-0 mt-2">
                            <?php foreach (($qset['questions'] ?? []) as $q): ?>
                                <?php $v = trim((string) ($ans[$q['key']] ?? '')); if ($v === '') continue; ?>
                                <dt class="col-sm-5 text-muted fw-normal"><?= e($q['label']) ?></dt>
                                <dd class="col-sm-7"><?= nl2br(e($v)) ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add / edit engagement modal -->
<div class="modal fade" id="engModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="engForm" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" id="engMethod" value="">
            <div class="modal-header"><h5 class="modal-title" id="engTitle">Add Service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">From Catalogue (optional)</label>
                    <select class="form-select" id="engService" name="service_id">
                        <option value="">Custom…</option>
                        <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" id="engLabel" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label">Billing</label>
                        <select name="billing_type" id="engBilling" class="form-select">
                            <option value="one_off">One-off</option>
                            <option value="recurring">Recurring</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Interval</label>
                        <select name="interval" id="engInterval" class="form-select">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col">
                        <label class="form-label">Price (<?= e($currency) ?>)</label>
                        <input type="number" step="0.01" name="price" id="engPrice" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label">Next Invoice</label>
                        <input type="date" name="next_invoice_date" id="engNext" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label">Status</label>
                        <select name="status" id="engStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const CLIENT_SERVICES = <?= json_encode($servicesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const STORE_URL = "<?= route('admin.engagements.store', ['id' => $client['id']]) ?>";
const UPDATE_URL = "<?= url('admin/engagements') ?>/";

function engAdd() {
    document.getElementById('engTitle').textContent = 'Add Service';
    document.getElementById('engForm').action = STORE_URL;
    document.getElementById('engMethod').value = '';
    document.getElementById('engService').value = '';
    document.getElementById('engLabel').value = '';
    document.getElementById('engBilling').value = 'one_off';
    document.getElementById('engInterval').value = 'monthly';
    document.getElementById('engPrice').value = '';
    document.getElementById('engNext').value = '';
    document.getElementById('engStatus').value = 'active';
}

function engEdit(e) {
    document.getElementById('engTitle').textContent = 'Edit Service';
    document.getElementById('engForm').action = UPDATE_URL + e.id;
    document.getElementById('engMethod').value = 'PUT';
    document.getElementById('engService').value = e.service_id || '';
    document.getElementById('engLabel').value = e.label;
    document.getElementById('engBilling').value = e.billing_type;
    document.getElementById('engInterval').value = e.interval || 'monthly';
    document.getElementById('engPrice').value = (e.price_cents / 100).toFixed(2);
    document.getElementById('engNext').value = e.next_invoice_date || '';
    document.getElementById('engStatus').value = e.status;
    new bootstrap.Modal(document.getElementById('engModal')).show();
}

document.getElementById('engService').addEventListener('change', function () {
    const s = CLIENT_SERVICES[this.value];
    if (s) {
        document.getElementById('engLabel').value = s.name;
        document.getElementById('engPrice').value = s.price;
        document.getElementById('engBilling').value = s.billing;
        if (s.interval) document.getElementById('engInterval').value = s.interval;
    }
});
</script>
<?php $this->endSection(); ?>
