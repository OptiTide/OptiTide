<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <?php if ($connected): ?>
                <span class="badge text-bg-success mb-1"><i class="bi bi-check-circle"></i> WHM connected</span>
                <div class="text-muted small"><?= e($server) ?> · <?= e($host) ?></div>
            <?php else: ?>
                <span class="badge text-bg-warning mb-1"><i class="bi bi-exclamation-triangle"></i> WHM not connected</span>
                <div class="text-muted small">Add <code>WHM_HOST</code>, <code>WHM_USERNAME</code> and <code>WHM_API_TOKEN</code> to your <code>.env</code> to enable account syncing.</div>
            <?php endif; ?>
        </div>
        <form method="post" action="<?= route('admin.hosting.sync') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-brand <?= $connected ? '' : 'disabled' ?>" <?= $connected ? '' : 'aria-disabled="true"' ?>><i class="bi bi-arrow-repeat"></i> Sync Accounts</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Hosting Accounts <span class="text-muted">(<?= count($accounts) ?>)</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Domain</th><th>Username</th><th>Plan</th><th>Disk</th><th>Status</th><th>Client</th><th>Synced</th></tr></thead>
            <tbody>
                <?php foreach ($accounts as $a): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($a['domain']) ?><?php if ($a['ip_address']): ?><div class="text-muted small"><?= e($a['ip_address']) ?></div><?php endif; ?></td>
                        <td class="font-monospace small"><?= e($a['username']) ?></td>
                        <td><?= e($a['plan'] ?: '—') ?></td>
                        <td class="text-nowrap small">
                            <?php if ($a['disk_used_mb'] !== null): ?>
                                <?= number_format((int) $a['disk_used_mb']) ?><?= $a['disk_limit_mb'] !== null ? ' / ' . number_format((int) $a['disk_limit_mb']) : '' ?> MB
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="badge <?= $a['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
                        <td>
                            <form method="post" action="<?= route('admin.hosting.assign', ['id' => $a['id']]) ?>" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:150px">
                                    <option value="">— Unassigned —</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (string) $a['client_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <noscript><button class="btn btn-sm btn-outline-brand">Set</button></noscript>
                            </form>
                        </td>
                        <td class="text-nowrap text-muted small"><?= e($a['synced_at'] ? date('d M Y', strtotime($a['synced_at'])) : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($accounts === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hosting accounts yet. <?= $connected ? 'Click “Sync Accounts” to import them from WHM.' : 'Connect WHM to sync accounts.' ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
