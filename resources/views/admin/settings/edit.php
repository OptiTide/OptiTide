<?php
$this->extends('layouts.admin');
$addr = $company['address'];
?>
<?php $this->section('content'); ?>

<form method="post" action="<?= route('admin.settings.update') ?>" novalidate>
    <?= csrf_field() ?><?= method_field('PUT') ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Company (Tax Invoice Identity)</div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Brand Name</label>
                            <input type="text" name="s_brand_name" value="<?= e($company['brand_name'] ?? '') ?>" maxlength="60" class="form-control">
                            <div class="form-text">Your trading name. Used for e-mail sender name, subject lines, invoice header and page titles.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Legal Name</label>
                            <input type="text" name="s_legal_name" value="<?= e($company['legal_name']) ?>" maxlength="120" class="form-control">
                            <div class="form-text">The entity your ABN is registered to — shown on tax invoices.</div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ABN</label>
                            <input type="text" name="s_abn" value="<?= e($company['abn']) ?>" class="form-control <?= has_error('s_abn') ? 'is-invalid' : '' ?>" placeholder="12 345 678 901">
                            <?php if (error('s_abn')): ?><div class="invalid-feedback"><?= e(error('s_abn')) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="s_phone" value="<?= e($company['phone']) ?>" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Company E-Mail</label>
                            <input type="email" name="s_email" value="<?= e($company['email']) ?>" class="form-control <?= has_error('s_email') ? 'is-invalid' : '' ?>">
                            <?php if (error('s_email')): ?><div class="invalid-feedback"><?= e(error('s_email')) ?></div><?php endif; ?>
                            <div class="form-text">Shown across the site <strong>and</strong> used as the “from” address on every e-mail we send — so its domain must be verified with your mail provider.</div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="text-muted small text-uppercase mb-2" style="letter-spacing:.05em">Business Address</div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Street</label><input type="text" name="s_addr_line1" value="<?= e($addr['line1']) ?>" class="form-control"></div>
                        <div class="col-md-5"><label class="form-label">Suburb / City</label><input type="text" name="s_addr_locality" value="<?= e($addr['locality']) ?>" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">State</label>
                            <select name="s_addr_region" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['NSW','VIC','QLD','WA','SA','TAS','ACT','NT'] as $st): ?>
                                    <option value="<?= $st ?>" <?= ($addr['region'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Postcode</label><input type="text" name="s_addr_postcode" value="<?= e($addr['postcode']) ?>" maxlength="4" class="form-control <?= has_error('s_addr_postcode') ? 'is-invalid' : '' ?>"></div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Business hours</label>
                            <input type="text" name="s_hours" value="<?= e($company['hours'] ?? '') ?>" maxlength="80" class="form-control <?= has_error('s_hours') ? 'is-invalid' : '' ?>" placeholder="Mon – Fri, 9am – 5pm AEST">
                            <div class="form-text">Shown in the website top bar, footer and contact page.</div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12"><label class="form-label mb-1">Social profiles <span class="text-muted small fw-normal">— leave blank to hide that icon</span></label></div>
                        <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-facebook"></i></span><input type="url" name="s_social_fb" value="<?= e($company['social']['facebook'] ?? '') ?>" class="form-control" placeholder="https://facebook.com/…"></div></div>
                        <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-instagram"></i></span><input type="url" name="s_social_ig" value="<?= e($company['social']['instagram'] ?? '') ?>" class="form-control" placeholder="https://instagram.com/…"></div></div>
                        <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-linkedin"></i></span><input type="url" name="s_social_li" value="<?= e($company['social']['linkedin'] ?? '') ?>" class="form-control" placeholder="https://linkedin.com/company/…"></div></div>
                    </div>
                    <div class="form-text mt-2">GST: <?= $company['gst_registered'] ? 'Registered (' . e(\App\Support\Gst::rateLabel()) . ' inclusive)' : 'Not registered' ?> · Currency: <?= e($company['currency']) ?> (set in .env)</div>
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
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">PayID Type</label>
                            <select name="s_payid_type" class="form-select">
                                <?php foreach (['mobile' => 'Mobile', 'email' => 'E-Mail', 'abn' => 'ABN'] as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($payid['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7"><label class="form-label">PayID Value</label><input type="text" name="s_payid_value" value="<?= e($payid['value']) ?>" class="form-control" placeholder="0400 000 000"></div>
                        <div class="col-12"><label class="form-label">Account Name</label><input type="text" name="s_payid_name" value="<?= e($payid['account_name']) ?>" class="form-control"></div>
                        <div class="col-md-5"><label class="form-label">BSB</label><input type="text" name="s_bank_bsb" value="<?= e($payid['bsb']) ?>" class="form-control" placeholder="000-000"></div>
                        <div class="col-md-7"><label class="form-label">Account Number</label><input type="text" name="s_bank_account" value="<?= e($payid['account_number']) ?>" class="form-control"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Payoneer</span>
                    <?= in_array('payoneer', $enabled, true) ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge text-bg-secondary">Off</span>' ?>
                </div>
                <div class="card-body">
                    <label class="form-label">Mode</label>
                    <select name="s_payoneer_mode" class="form-select">
                        <option value="manual" <?= ($payoneer['mode'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual — paste a link per invoice</option>
                        <option value="api" <?= ($payoneer['mode'] ?? '') === 'api' ? 'selected' : '' ?>>API — auto-generate (needs credentials)</option>
                    </select>
                    <div class="form-text">Payoneer / Resend API keys stay in <code>.env</code> for security.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Skrill</span>
                    <?= in_array('skrill', $enabled, true) ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge text-bg-secondary">Off</span>' ?>
                </div>
                <div class="card-body">
                    <label class="form-label">Merchant Skrill e-mail</label>
                    <input type="email" name="s_skrill_email" value="<?= e($skrill['merchant_email'] ?? '') ?>" class="form-control <?= has_error('s_skrill_email') ? 'is-invalid' : '' ?>" placeholder="payments@yourbusiness.com">
                    <?php if (error('s_skrill_email')): ?><div class="invalid-feedback"><?= e(error('s_skrill_email')) ?></div><?php endif; ?>
                    <div class="form-text">The e-mail on your Skrill merchant account. Clients pay via Skrill Quick Checkout.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>PayPal</span>
                    <?= in_array('paypal', $enabled, true) ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge text-bg-secondary">Off</span>' ?>
                </div>
                <div class="card-body">
                    <label class="form-label">PayPal.Me handle</label>
                    <div class="input-group">
                        <span class="input-group-text">paypal.me/</span>
                        <input type="text" name="s_paypal_handle" value="<?= e($paypal['me_handle'] ?? '') ?>" class="form-control" placeholder="YourBusiness">
                    </div>
                    <div class="form-text">Your PayPal.Me handle. Clients are sent a pre-filled PayPal link for the invoice balance.</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-sliders text-brand"></i><span>Invoicing Defaults</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Invoice Footer Note</label>
                            <textarea name="invoice_footer" rows="2" maxlength="500" class="form-control" placeholder="e.g. Thank you for your business."><?= e(\App\Models\Setting::get('invoice_footer', '')) ?></textarea>
                            <div class="form-text">Printed at the bottom of every invoice PDF.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Payment Terms (Days)</label>
                            <input type="number" name="default_payment_terms" min="0" max="120" value="<?= e(\App\Models\Setting::get('default_payment_terms', '14')) ?>" class="form-control <?= has_error('default_payment_terms') ? 'is-invalid' : '' ?>">
                            <?php if (error('default_payment_terms')): ?><div class="invalid-feedback"><?= e(error('default_payment_terms')) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-graph-up text-brand"></i><span>Analytics &amp; Tracking</span></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Paste the ID only (never a full script). We emit the official snippet on your public pages. Leave blank to disable. Invalid formats are simply ignored.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Google Analytics 4 — Measurement ID</label>
                            <input type="text" name="s_ga4" value="<?= e($analytics['ga4'] ?? '') ?>" class="form-control" placeholder="G-XXXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Tag Manager — Container ID</label>
                            <input type="text" name="s_gtm" value="<?= e($analytics['gtm'] ?? '') ?>" class="form-control" placeholder="GTM-XXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Search Console — Verification token</label>
                            <input type="text" name="s_gsc" value="<?= e($analytics['gsc'] ?? '') ?>" class="form-control" placeholder="content value from the meta tag">
                            <div class="form-text">Choose the “HTML tag” method in Search Console and paste the <code>content</code> value.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta (Facebook) Pixel ID</label>
                            <input type="text" name="s_meta_pixel" value="<?= e($analytics['meta_pixel'] ?? '') ?>" class="form-control" placeholder="123456789012345">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3"><button class="btn btn-brand btn-lg"><i class="bi bi-check-lg"></i> Save Settings</button></div>
</form>
<?php $this->endSection(); ?>
