<?php
/**
 * Add / edit a client app. Billing here never invoices on its own: a recurring
 * app names the engagement the recurring biller already charges for, and only a
 * one-off carries a price of its own.
 *
 * @var string     $action       form target
 * @var array|null $app          the app being edited, null when adding
 * @var array      $engagements  this client's engagements, for the picker
 */
$appId = $app['id'] ?? 'new';
$billing = $app['billing_type'] ?? \App\Models\ClientApp::BILLING_NONE;
$appCurrency = config('company.currency', 'AUD');
// Only a one-off carries its own price, so only that seeds the dollars input.
$priceValue = ($app && $billing === \App\Models\ClientApp::BILLING_ONE_OFF && $app['price_cents'] !== null)
    ? number_format((int) $app['price_cents'] / 100, 2, '.', '')
    : '';
$billable = array_values(array_filter(
    $engagements,
    fn ($engagement) => $engagement['status'] !== \App\Models\ClientService::STATUS_CANCELLED
));
?>
<form method="post" action="<?= e($action) ?>" class="row g-2 align-items-end mb-3" data-app-form>
    <?= csrf_field() ?>
    <?php if ($app): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="col-sm-4"><label class="form-label small">Name</label><input type="text" name="name" class="form-control form-control-sm" required maxlength="120" placeholder="Client Dashboard" value="<?= e($app['name'] ?? '') ?>"></div>
    <div class="col-sm-5"><label class="form-label small">URL</label><input type="url" name="url" class="form-control form-control-sm" required maxlength="300" placeholder="https://app.client.com" value="<?= e($app['url'] ?? '') ?>"></div>
    <div class="col-sm-3"><label class="form-label small">Environment</label><input type="text" name="environment" class="form-control form-control-sm" maxlength="40" placeholder="Production" value="<?= e($app['environment'] ?? '') ?>"></div>

    <div class="col-sm-4">
        <label class="form-label small" for="appBilling<?= e((string) $appId) ?>">Billing</label>
        <select name="billing_type" id="appBilling<?= e((string) $appId) ?>" class="form-select form-select-sm" data-app-billing>
            <?php foreach (\App\Models\ClientApp::BILLING_TYPES as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $billing === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-5" data-app-engagement<?= $billing === \App\Models\ClientApp::BILLING_RECURRING ? '' : ' hidden' ?>>
        <label class="form-label small" for="appEng<?= e((string) $appId) ?>">Engagement</label>
        <select name="engagement_id" id="appEng<?= e((string) $appId) ?>" class="form-select form-select-sm">
            <option value="">Choose an engagement…</option>
            <?php foreach ($billable as $engagement): ?>
                <option value="<?= (int) $engagement['id'] ?>"<?= (string) ($app['engagement_id'] ?? '') === (string) $engagement['id'] ? ' selected' : '' ?>>
                    <?= e($engagement['label']) ?> — <?= e(money((int) $engagement['price_cents'], $engagement['currency'])->format()) ?><?= $engagement['billing_type'] === 'recurring' ? ' ' . e($engagement['interval'] ?? 'monthly') : ' one-off' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-3" data-app-price<?= $billing === \App\Models\ClientApp::BILLING_ONE_OFF ? '' : ' hidden' ?>>
        <label class="form-label small" for="appPrice<?= e((string) $appId) ?>">Price (<?= e($appCurrency) ?>)</label>
        <input type="number" step="0.01" min="0" name="price" id="appPrice<?= e((string) $appId) ?>" class="form-control form-control-sm" placeholder="0.00" value="<?= e($priceValue) ?>">
    </div>

    <div class="col-12">
        <button class="btn btn-sm btn-brand"><?= $app ? 'Save App' : 'Link App' ?></button>
        <span class="text-muted small">
            <?php if ($billable === []): ?>
                A Coolify-hosted app the client can open from their portal. Add a service to this client first if the app is billed monthly.
            <?php else: ?>
                A Coolify-hosted app the client can open from their portal. Recurring apps bill through their engagement, so they're never invoiced twice.
            <?php endif; ?>
        </span>
    </div>
</form>
