<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div class="small">
        <strong>How backlinking works here.</strong> Search engines reward links that are <em>earned</em>, not
        spammed — automated link-dropping breaches their guidelines and can get you penalised, so it isn't something
        that can be safely automated. This toolkit gives you a curated list of high-quality Australian citation sources
        to submit to (using the consistent business details below), and tracks your whole link profile from
        <em>to&nbsp;do → submitted → live</em>.
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $stat = [
        ['Total targets', $summary['total'], 'bi-link-45deg', ''],
        ['To do', $summary['prospect'] ?? 0, 'bi-list-check', 'prospect'],
        ['Submitted', $summary['submitted'] ?? 0, 'bi-hourglass-split', 'submitted'],
        ['Live links', $summary['live'] ?? 0, 'bi-check2-circle', 'live'],
    ];
    foreach ($stat as [$label, $value, $icon, $s]):
    ?>
        <div class="col-6 col-xl-3">
            <a href="<?= route('admin.backlinks.index') . ($s ? '?status=' . $s : '') ?>" class="text-decoration-none text-reset">
                <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div><div class="stat-value"><?= (int) $value ?></div><div class="stat-label"><?= e($label) ?></div></div>
                </div></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-building"></i> Your business details</span>
                <button class="btn btn-sm btn-light" type="button" onclick="otCopyNap()" title="Copy all"><i class="bi bi-clipboard"></i></button>
            </div>
            <div class="card-body small" id="napBlock">
                <p class="text-muted mb-2">Use these <strong>exactly the same</strong> every time (consistent NAP builds local-SEO trust):</p>
                <dl class="row mb-0">
                    <dt class="col-4 text-muted fw-normal">Name</dt><dd class="col-8"><?= e($nap['name']) ?></dd>
                    <?php if ($nap['address']): ?><dt class="col-4 text-muted fw-normal">Address</dt><dd class="col-8"><?= e($nap['address']) ?></dd><?php endif; ?>
                    <?php if ($nap['phone']): ?><dt class="col-4 text-muted fw-normal">Phone</dt><dd class="col-8"><?= e($nap['phone']) ?></dd><?php endif; ?>
                    <dt class="col-4 text-muted fw-normal">Email</dt><dd class="col-8"><?= e($nap['email']) ?></dd>
                    <dt class="col-4 text-muted fw-normal">Website</dt><dd class="col-8"><?= e($nap['website']) ?></dd>
                    <dt class="col-4 text-muted fw-normal">ABN</dt><dd class="col-8"><?= e($nap['abn']) ?></dd>
                </dl>
                <?php if (! $nap['phone'] || ! $nap['address']): ?>
                    <div class="text-warning mt-2"><i class="bi bi-exclamation-triangle"></i> Add a phone &amp; address in your <code>.env</code> (COMPANY_PHONE / COMPANY_ADDRESS_*) — most directories require them.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Add a target</div>
            <div class="card-body">
                <form method="post" action="<?= route('admin.backlinks.store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2"><label class="form-label small mb-1" for="bl_site">Site name</label><input id="bl_site" type="text" name="site_name" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small mb-1" for="bl_submit">Submit / claim URL</label><input id="bl_submit" type="url" name="submit_url" class="form-control form-control-sm" placeholder="https://…"></div>
                    <div class="mb-2"><label class="form-label small mb-1" for="bl_type">Type</label>
                        <select id="bl_type" name="type" class="form-select form-select-sm">
                            <?php foreach (\App\Models\Backlink::TYPES as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small mb-1" for="bl_notes">Notes</label><input id="bl_notes" type="text" name="notes" class="form-control form-control-sm"></div>
                    <button class="btn btn-sm btn-brand w-100">Add target</button>
                </form>
            </div>
        </div>

        <form method="post" action="<?= route('admin.backlinks.seed') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-brand w-100"><i class="bi bi-cloud-download"></i> Load AU directory starter list</button>
            <div class="form-text">Adds 20+ vetted Australian directories &amp; agency listings as "to do". Safe to click again — it never duplicates.</div>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Link profile</span>
                <div class="btn-group btn-group-sm">
                    <a href="<?= route('admin.backlinks.index') ?>" class="btn <?= $filter === '' ? 'btn-brand' : 'btn-outline-secondary' ?>">All</a>
                    <?php foreach (\App\Models\Backlink::STATUSES as $k => $v): ?>
                        <a href="<?= route('admin.backlinks.index') ?>?status=<?= $k ?>" class="btn <?= $filter === $k ? 'btn-brand' : 'btn-outline-secondary' ?>"><?= e($v) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Site</th><th>Type</th><th class="text-center">DA</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if ($links === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">
                                No targets yet. Click <strong>Load AU directory starter list</strong> to get a ready-made to-do list.
                            </td></tr>
                        <?php endif; ?>
                        <?php foreach ($links as $b): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($b['site_name']) ?></div>
                                    <?php if (! empty($b['notes'])): ?><div class="small text-muted"><?= e($b['notes']) ?></div><?php endif; ?>
                                    <?php if (! empty($b['submit_url'])): ?><a href="<?= e($b['submit_url']) ?>" target="_blank" rel="noopener nofollow" class="small">Open submit page <i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e(\App\Models\Backlink::TYPES[$b['type']] ?? $b['type']) ?></td>
                                <td class="text-center small"><?= $b['domain_authority'] ? (int) $b['domain_authority'] : '—' ?></td>
                                <td>
                                    <form method="post" action="<?= route('admin.backlinks.update', ['id' => $b['id']]) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <select name="status" class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="this.form.submit()">
                                            <?php foreach (\App\Models\Backlink::STATUSES as $k => $v): ?>
                                                <option value="<?= $k ?>" <?= $b['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="post" action="<?= route('admin.backlinks.destroy', ['id' => $b['id']]) ?>" onsubmit="return confirm('Remove this target?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger" aria-label="Remove"><i class="bi bi-trash"></i></button></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function otCopyNap() {
    var el = document.getElementById('napBlock');
    var text = el.innerText.replace(/\s*\n\s*/g, '\n').trim();
    navigator.clipboard && navigator.clipboard.writeText(text);
}
</script>
<?php $this->endSection(); ?>
