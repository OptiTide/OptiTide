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

        <?php
        // Portal access. Without this the ONLY way to invite anyone was the checkbox
        // on the create form — so if it was unticked, or the invite failed, there was
        // no way back to it in the UI.
        $portalUser = \App\Models\User::query()
            ->where('client_id', $client['id'])
            ->where('role', \App\Models\User::ROLE_CLIENT)
            ->first();
        $activated = $portalUser && ! empty($portalUser['password_hash']);
        ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-person-badge"></i> Portal Access</div>
            <div class="card-body">
                <?php if (! $portalUser): ?>
                    <p class="small text-muted mb-2">No portal login yet — <?= e($client['business_name']) ?> can't see invoices or track their project.</p>
                <?php elseif (! $activated): ?>
                    <p class="small mb-2">
                        <span class="badge text-bg-warning">Invited</span>
                        <span class="text-muted d-block mt-1">They haven't set a password yet. Links last 7 days — resend if theirs has expired.</span>
                    </p>
                <?php else: ?>
                    <p class="small mb-2">
                        <span class="badge text-bg-success">Active</span>
                        <span class="text-muted d-block mt-1">Signing in as <?= e($portalUser['email']) ?>.</span>
                    </p>
                <?php endif; ?>
                <form method="post" action="<?= route('admin.clients.invite', ['id' => $client['id']]) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm <?= $activated ? 'btn-light' : 'btn-brand' ?> w-100" <?= empty($client['email']) ? 'disabled' : '' ?>>
                        <i class="bi bi-envelope"></i>
                        <?= $portalUser ? 'Resend Invite' : 'Send Portal Invite' ?>
                    </button>
                </form>
                <?php if (empty($client['email'])): ?>
                    <div class="form-text text-danger">Add an email address first.</div>
                <?php elseif ($activated): ?>
                    <div class="form-text">Resending lets them set a new password. Their old one stops working only once they use the link.</div>
                <?php endif; ?>
                <?php if ($portalUser): ?>
                    <a href="<?= route('admin.users.edit', ['id' => $portalUser['id']]) ?>" class="btn btn-sm btn-link w-100 mt-1">
                        Edit login (email, name, or move to another client)
                    </a>
                <?php endif; ?>
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
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Nothing sold to this client yet.
                                    <div class="small mt-1 mb-2">An engagement is a plan they're on. Recurring ones are invoiced for you on their schedule.</div>
                                    <button class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#engModal" onclick="engAdd()"><i class="bi bi-plus-lg"></i> Add a Service</button>
                                </td>
                            </tr>
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
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    This client has never been invoiced.
                                    <div class="mt-2">
                                        <a href="<?= route('admin.invoices.create') ?>?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-brand"><i class="bi bi-receipt"></i> Raise an Invoice</a>
                                        <?php if (\App\Support\Features::enabled('quotes')): ?>
                                            <a href="<?= route('admin.quotes.create') ?>?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-file-earmark-text"></i> Send a Quote</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
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

        <?php if (\App\Support\Features::enabled('quotes')): ?>
            <div class="card mt-3" id="quotes">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text"></i> Quotes</span>
                    <a href="<?= route('admin.quotes.create') ?>?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-plus-lg"></i> New Quote</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Quote</th><th>Issued</th><th>Expires</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $quotes): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">
                                    No quotes for this client.
                                    <div class="small mt-1">Send a price before the work starts — they accept it from a link and it becomes an invoice by itself.</div>
                                </td></tr>
                            <?php endif; ?>
                            <?php foreach (array_slice($quotes, 0, 8) as $quote): ?>
                                <?php $display = \App\Models\Quote::displayStatus($quote); ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($quote['number']) ?></td>
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
                <?php if (count($quotes) > 8): ?>
                    <div class="card-footer text-end small text-muted">Showing the 8 most recent of <?= count($quotes) ?>.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card mt-3" id="tickets">
            <div class="card-header"><i class="bi bi-life-preserver"></i> Support History</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Ref</th><th>Subject</th><th>Priority</th><th>Status</th><th>Last Update</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $tickets): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                No tickets from this client.
                                <div class="small mt-1">They raise these from their portal, or by e-mailing your support address.</div>
                            </td></tr>
                        <?php endif; ?>
                        <?php foreach (array_slice($tickets, 0, 8) as $ticket): ?>
                            <?php $tBadge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary']; ?>
                            <?php $tPrio = ['high' => 'text-bg-danger', 'normal' => 'badge-soft', 'low' => 'text-bg-light']; ?>
                            <tr>
                                <td class="text-muted small"><?= e($ticket['number']) ?></td>
                                <td class="fw-semibold"><?= e($ticket['subject']) ?></td>
                                <td><span class="badge <?= $tPrio[$ticket['priority']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::PRIORITIES[$ticket['priority']] ?? ucfirst((string) $ticket['priority'])) ?></span></td>
                                <td><span class="badge <?= $tBadge[$ticket['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::STATUSES[$ticket['status']] ?? ucfirst((string) $ticket['status'])) ?></span></td>
                                <td class="text-nowrap text-muted small"><?= e($ticket['last_reply_at'] ? date('d M Y', strtotime($ticket['last_reply_at'])) : '—') ?></td>
                                <td class="text-end"><a href="<?= route('admin.tickets.show', ['id' => $ticket['id']]) ?>" class="btn btn-sm btn-light">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($tickets) > 8): ?>
                <div class="card-footer text-end small text-muted">Showing the 8 most recent of <?= count($tickets) ?>.</div>
            <?php endif; ?>
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

        <div class="card mt-3" id="credit">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wallet2"></i> Account Credit</span>
                <span class="fw-bold money"><?= e(money((int) ($client['credit_cents'] ?? 0), config('company.currency', 'AUD'))->format()) ?></span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= route('admin.clients.credit', ['id' => $client['id']]) ?>" class="row g-2 align-items-end mb-3">
                    <?= csrf_field() ?>
                    <div class="col-sm-4">
                        <label class="form-label small">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="e.g. 50 or -20" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small">Reason</label>
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="200" placeholder="e.g. Goodwill credit">
                    </div>
                    <div class="col-sm-2"><button class="btn btn-sm btn-brand w-100">Add</button></div>
                    <div class="form-text">Use a negative amount to deduct. Credit can be applied to invoices.</div>
                </form>
                <?php if (! empty($credit_txns)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Date</th><th>Type</th><th>Reason</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($credit_txns, 0, 8) as $tx): ?>
                                    <tr>
                                        <td class="text-muted small"><?= e($tx['created_at'] ? date('d M Y', strtotime($tx['created_at'])) : '') ?></td>
                                        <td><span class="badge badge-soft"><?= e(ucfirst($tx['type'])) ?></span></td>
                                        <td class="small"><?= e($tx['reason'] ?: '—') ?></td>
                                        <td class="text-end money <?= (int) $tx['amount_cents'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $tx['amount_cents'] < 0 ? '−' : '+' ?><?= e(money(abs((int) $tx['amount_cents']), config('company.currency', 'AUD'))->format()) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-3" id="apicredit">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cpu"></i> API Credit</span>
                <span class="fw-bold money"><?= e(money((int) ($client['api_credit_cents'] ?? 0), config('company.currency', 'AUD'))->format()) ?></span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= route('admin.clients.apicredit', ['id' => $client['id']]) ?>" class="row g-2 align-items-end mb-0">
                    <?= csrf_field() ?>
                    <div class="col-sm-4">
                        <label class="form-label small">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="e.g. 50 or -20" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small">Reason</label>
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="200" placeholder="e.g. Comped API credit">
                    </div>
                    <div class="col-sm-2"><button class="btn btn-sm btn-brand w-100">Adjust</button></div>
                    <div class="form-text">Prepaid balance for the white-label <?= e(config('company.brand_name')) ?> API. Clients also top up by paying a credit invoice.</div>
                </form>
            </div>
        </div>
        <div class="card mt-3" id="apps">
            <div class="card-header"><i class="bi bi-window-stack"></i> Client Apps</div>
            <div class="card-body">
                <?php $this->insert('partials.client-app-form', [
                    'action'      => route('admin.clients.apps.store', ['id' => $client['id']]),
                    'app'         => null,
                    'engagements' => $engagements,
                ]); ?>
                <?php foreach ($apps as $app): ?>
                    <?php $engagement = $app_engagements[$app['engagement_id']] ?? null; ?>
                    <?php $price = \App\Models\ClientApp::priceLabel($app, $engagement); ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <span class="fw-semibold"><?= e($app['name']) ?></span>
                            <?php if ($app['environment']): ?><span class="badge badge-soft ms-1"><?= e($app['environment']) ?></span><?php endif; ?>
                            <div class="text-muted small"><?= e($app['url']) ?></div>
                            <?php if ($price !== ''): ?>
                                <div class="small">
                                    <span class="money fw-semibold"><?= e($price) ?></span>
                                    <?php if ($engagement): ?>
                                        <span class="text-muted">— billed under <?= e($engagement['label']) ?><?= ! empty($engagement['reference']) ? ' (' . e($engagement['reference']) . ')' : '' ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">— one-off, invoice by hand</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">Not billed</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#appEdit<?= (int) $app['id'] ?>">Edit</button>
                            <form method="post" action="<?= route('admin.apps.destroy', ['id' => $app['id']]) ?>" onsubmit="return confirm('Unlink this app?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger">Remove</button></form>
                        </div>
                    </div>
                    <div class="collapse py-2 border-bottom" id="appEdit<?= (int) $app['id'] ?>">
                        <?php $this->insert('partials.client-app-form', [
                            'action'      => route('admin.apps.update', ['id' => $app['id']]),
                            'app'         => $app,
                            'engagements' => $engagements,
                        ]); ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($apps === []): ?><div class="text-muted small">No apps linked yet.</div><?php endif; ?>
            </div>
        </div>
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

// App billing: only a one-off takes a price of its own; a recurring app must
// name the engagement that already bills it. Delegated — the app form partial
// is rendered once per app plus once for the add row.
document.addEventListener('change', function (ev) {
    const select = ev.target.closest('[data-app-billing]');
    if (!select) return;
    const form = select.closest('[data-app-form]');
    form.querySelector('[data-app-engagement]').hidden = select.value !== 'recurring';
    form.querySelector('[data-app-price]').hidden = select.value !== 'one_off';
});
</script>
<?php $this->endSection(); ?>
