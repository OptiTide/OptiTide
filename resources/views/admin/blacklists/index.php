<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <p class="text-muted small mb-0" style="max-width:620px">
                Client domains and mail IPs are checked against the public spam blacklists
                (Spamhaus, SpamCop, SORBS, SURBL). When something gets <strong>listed</strong>, a card
                appears on the SEO or Hosting board automatically — one card per incident, cleared
                for re-use when the listing drops. A blacklisted mail IP means your client's
                invoices and quotes are going to spam folders; that's why this is worth watching.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="<?= route('admin.blacklists.seed') ?>"><?= csrf_field() ?>
                    <button class="btn btn-outline-brand btn-sm"><i class="bi bi-hdd-network"></i> Add All Hosting Domains</button>
                </form>
                <form method="post" action="<?= route('admin.blacklists.check') ?>"><?= csrf_field() ?>
                    <button class="btn btn-brand btn-sm"><i class="bi bi-radar"></i> Check Now</button>
                </form>
            </div>
        </div>

        <form method="post" action="<?= route('admin.blacklists.store') ?>">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-3 col-12">
                    <label class="form-label small">Domain or IP</label>
                    <input type="text" name="value" class="form-control form-control-sm <?= has_error('value') ? 'is-invalid' : '' ?>" placeholder="clientsite.com.au or 203.0.113.7" required autocapitalize="none">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="domain">Domain</option>
                        <option value="ip">Mail/server IP</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small">Cards go to</label>
                    <select name="board" class="form-select form-select-sm">
                        <option value="hosting">Hosting board</option>
                        <option value="seo">SEO board</option>
                    </select>
                </div>
                <div class="col-md-3 col-8">
                    <label class="form-label small">Client <span class="text-muted">(optional)</span></label>
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['business_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-4">
                    <button class="btn btn-brand btn-sm w-100"><i class="bi bi-plus-lg"></i> Monitor</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Monitored Targets <span class="text-muted">(<?= count($targets) ?>)</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Target</th><th>Type</th><th>Status</th><th>Client</th><th>Board</th><th>Last Checked</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($targets as $t): ?>
                    <tr>
                        <td class="fw-semibold font-monospace small"><?= e($t['value']) ?></td>
                        <td class="small text-muted"><?= e($t['type']) ?></td>
                        <td>
                            <?php if ($t['status'] === 'listed'): ?>
                                <span class="badge text-bg-danger">Listed</span>
                                <div class="small text-danger"><?= e(implode(', ', (array) json_decode((string) $t['listed_on'], true))) ?></div>
                            <?php elseif ($t['status'] === 'ok'): ?>
                                <span class="badge text-bg-success">Clean</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Not checked yet</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($client_names[$t['client_id']] ?? '—') ?></td>
                        <td class="small text-capitalize"><?= e($t['board']) ?></td>
                        <td class="small text-muted text-nowrap"><?= e($t['last_checked_at'] ? date('d M H:i', strtotime($t['last_checked_at'])) : '—') ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= route('admin.blacklists.destroy', ['id' => $t['id']]) ?>" onsubmit="return confirm('Stop monitoring <?= e($t['value']) ?>?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($targets === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-shield-check fs-3 d-block mb-2 opacity-50"></i>
                        Nothing monitored yet. Add a domain above, or pull in every hosting account in one go.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
