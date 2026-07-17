<?php
$this->extends('layouts.portal');
$typeBadge = ['topup' => 'text-bg-success', 'usage' => 'text-bg-secondary', 'adjust' => 'text-bg-info', 'refund' => 'text-bg-warning'];
?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">Power your own apps with the <?= e(config('company.brand_name')) ?> API — buy prepaid credit, generate a key, and start building. You only pay for what you use.</p>

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card h-100 border-brand">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Credit balance</div>
                <div class="display-6 fw-bold text-brand money"><?= e(money((int) $balance, config('company.currency', 'AUD'))->format()) ?></div>
                <?php if ($balance <= 0): ?>
                    <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle"></i> Top up to start making API calls.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Buy Credit</h6>
                <form method="post" action="<?= route('portal.api.buy') ?>" class="d-flex flex-wrap gap-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="btn-group flex-wrap" role="group">
                        <?php foreach ($packs as $pack): ?>
                            <button type="submit" name="amount" value="<?= (int) ($pack / 100) ?>" class="btn btn-outline-brand">$<?= number_format($pack / 100) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="input-group" style="max-width:220px">
                        <span class="input-group-text">$</span>
                        <?php // From the config the server actually enforces — a hardcoded
                              // minimum here would keep gating on the old value after the
                              // config changed, and reject what the server would accept. ?>
                        <input type="number" name="amount" min="<?= (int) ceil(config('api_credits.min_topup_cents', 1000) / 100) ?>" step="1" class="form-control" placeholder="Custom">
                        <button class="btn btn-brand" type="submit">Buy</button>
                    </div>
                </form>
                <div class="small text-muted mt-2">We'll raise an invoice; your credit is added automatically once it's paid. Prices are in AUD and include GST.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center"><span>Your API Key</span></div>
    <div class="card-body">
        <?php if ($newKey): ?>
            <div class="alert alert-success">
                <div class="fw-bold mb-1"><i class="bi bi-check-circle"></i> Here's your new API key — copy it now, it won't be shown again.</div>
                <code class="d-block p-2 bg-white border rounded" style="word-break:break-all"><?= e($newKey) ?></code>
            </div>
        <?php endif; ?>

        <?php if ($hasKey): ?>
            <p class="mb-2">Active key: <code><?= e($maskedKey) ?></code></p>
            <div class="d-flex gap-2">
                <form method="post" action="<?= route('portal.api.key') ?>" onsubmit="return confirm('Generate a new key? Your current key will stop working immediately.')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Rotate Key</button></form>
                <form method="post" action="<?= route('portal.api.key.revoke') ?>" onsubmit="return confirm('Revoke your key? API calls will stop working until you generate a new one.')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger">Revoke</button></form>
            </div>
        <?php else: ?>
            <p class="text-muted mb-2">You don't have an API key yet.</p>
            <form method="post" action="<?= route('portal.api.key') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-brand"><i class="bi bi-key"></i> Generate API Key</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Usage &amp; Top-Ups</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>When</th><th>Detail</th><th class="text-end">Amount</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                        <?php foreach ($ledger as $t): ?>
                            <tr>
                                <td class="small text-muted text-nowrap"><?= e(date('d M, H:i', strtotime($t['created_at']))) ?></td>
                                <td class="small"><span class="badge <?= $typeBadge[$t['type']] ?? 'text-bg-light' ?>"><?= e($t['type']) ?></span> <?= e($t['description']) ?></td>
                                <td class="text-end small money <?= (int) $t['delta_cents'] < 0 ? 'text-danger' : 'text-success' ?>"><?= (int) $t['delta_cents'] < 0 ? '−' : '+' ?><?= e(money(abs((int) $t['delta_cents']), config('company.currency', 'AUD'))->format()) ?></td>
                                <td class="text-end small money"><?= e(money((int) $t['balance_after_cents'], config('company.currency', 'AUD'))->format()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($ledger === []): ?><tr><td colspan="4" class="text-center text-muted py-4">No activity yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Quick Start</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Send a request to the <?= e(config('company.brand_name')) ?> API. Available models:</p>
                <ul class="small mb-3">
                    <?php foreach ($models as $alias => $_real): ?>
                        <li><code><?= e($alias) ?></code><?php if (isset($pricing[$alias])): ?> — <span class="text-muted">$<?= number_format($pricing[$alias]['in'] / 100, 2) ?>/$<?= number_format($pricing[$alias]['out'] / 100, 2) ?> per M tokens (in/out)</span><?php endif; ?></li>
                    <?php endforeach; ?>
                </ul>
                <pre class="bg-dark text-light p-3 rounded small" style="overflow-x:auto"><code>curl <?= e($apiBase) ?>/messages \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "content-type: application/json" \
  -d '{
    "model": "<?= e(config('api_credits.default_model')) ?>",
    "max_tokens": 512,
    "messages": [
      {"role":"user","content":"Hello!"}
    ]
  }'</code></pre>
                <p class="small text-muted mb-0">Check your balance any time: <code>GET <?= e($apiBase) ?>/credit</code></p>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
