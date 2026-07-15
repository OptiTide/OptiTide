<?php
$this->extends('layouts.admin');
$yes = fn ($v) => $v ? '<span class="badge text-bg-success">Configured</span>' : '<span class="badge text-bg-warning">Not set</span>';
?>
<?php $this->section('content'); ?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i> Company identity and payment credentials are read from <code>.env</code> for security.
    Edit that file (or your Coolify environment variables) and redeploy to change them.
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">Company (Tax Invoice Identity)</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Legal name</dt><dd class="col-7"><?= e($company['legal_name']) ?></dd>
                    <dt class="col-5 text-muted">ABN</dt><dd class="col-7"><?= $company['abn'] ? e($company['abn']) : '<span class="text-warning">Set COMPANY_ABN</span>' ?></dd>
                    <dt class="col-5 text-muted">E-Mail</dt><dd class="col-7"><?= e($company['email']) ?></dd>
                    <dt class="col-5 text-muted">GST registered</dt><dd class="col-7"><?= $company['gst_registered'] ? 'Yes (' . e(\App\Support\Gst::rateLabel()) . ' inclusive)' : 'No' ?></dd>
                    <dt class="col-5 text-muted">Currency</dt><dd class="col-7"><?= e($company['currency']) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">E-Mail (Resend)</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Driver</dt><dd class="col-7"><?= e($mail['driver']) ?></dd>
                    <dt class="col-5 text-muted">API key</dt><dd class="col-7"><?= $yes($mail['resend']['api_key'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">From</dt><dd class="col-7"><?= e($mail['from']['name']) ?> &lt;<?= e($mail['from']['address']) ?>&gt;</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>PayID / Bank Transfer</span>
                <?= in_array('payid', $enabled, true) ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge text-bg-secondary">Off</span>' ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">PayID type</dt><dd class="col-7"><?= e(ucfirst($payid['type'])) ?></dd>
                    <dt class="col-5 text-muted">PayID value</dt><dd class="col-7"><?= $payid['value'] ? e($payid['value']) : '<span class="text-warning">Set PAYID_VALUE</span>' ?></dd>
                    <dt class="col-5 text-muted">Account name</dt><dd class="col-7"><?= e($payid['account_name']) ?></dd>
                    <dt class="col-5 text-muted">BSB / Account</dt><dd class="col-7"><?= $payid['bsb'] ? e($payid['bsb'] . ' / ' . $payid['account_number']) : '—' ?></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Payoneer</span>
                <?= in_array('payoneer', $enabled, true) ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge text-bg-secondary">Off</span>' ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Mode</dt><dd class="col-7"><?= e($payoneer['mode']) ?> <span class="text-muted">(<?= $payoneer['mode'] === 'manual' ? 'paste link per invoice' : 'API' ?>)</span></dd>
                    <dt class="col-5 text-muted">API key</dt><dd class="col-7"><?= $yes($payoneer['api_key'] ?? '') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<form method="post" action="<?= route('admin.settings.update') ?>" class="card mt-3" novalidate>
    <?= csrf_field() ?><?= method_field('PUT') ?>
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-sliders text-brand"></i>
        <span>Invoicing Defaults</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="invoice_footer">Invoice Footer Note</label>
                <textarea id="invoice_footer" name="invoice_footer" rows="3" maxlength="500" class="form-control <?= has_error('invoice_footer') ? 'is-invalid' : '' ?>" placeholder="e.g. Thank You For Your Business."><?= e(old('invoice_footer', \App\Models\Setting::get('invoice_footer', ''))) ?></textarea>
                <?php if (error('invoice_footer')): ?><div class="invalid-feedback"><?= e(error('invoice_footer')) ?></div><?php endif; ?>
                <div class="form-text">Printed at the bottom of every invoice PDF. Up to 500 characters.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="default_payment_terms">Default Payment Terms (Days)</label>
                <div class="input-group">
                    <input type="number" id="default_payment_terms" name="default_payment_terms" min="0" max="120" step="1"
                           value="<?= e(old('default_payment_terms', \App\Models\Setting::get('default_payment_terms', '14'))) ?>"
                           class="form-control <?= has_error('default_payment_terms') ? 'is-invalid' : '' ?>">
                    <span class="input-group-text">Days</span>
                </div>
                <?php if (error('default_payment_terms')): ?><div class="text-danger small mt-1"><?= e(error('default_payment_terms')) ?></div><?php endif; ?>
                <div class="form-text">Due date offset for new invoices (0–120). Blank defaults to 14.</div>
            </div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-brand"><i class="bi bi-check-lg"></i> Save Settings</button></div>
</form>
<?php $this->endSection(); ?>
