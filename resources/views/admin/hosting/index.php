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
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="<?= route('admin.hosting.sync') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-brand <?= $connected ? '' : 'disabled' ?>" <?= $connected ? '' : 'aria-disabled="true"' ?>><i class="bi bi-arrow-repeat"></i> Sync Accounts</button>
            </form>
            <?php if ($connected): ?>
                <button class="btn btn-outline-brand" type="button" data-bs-toggle="collapse" data-bs-target="#newAccount"><i class="bi bi-plus-lg"></i> New Account</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($connected): ?>
<div class="collapse mb-3" id="newAccount">
    <div class="card">
        <div class="card-header">Provision a New cPanel Account</div>
        <div class="card-body">
            <form method="post" action="<?= route('admin.hosting.create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label">Domain</label>
                        <input type="text" name="domain" class="form-control" placeholder="clientsite.com.au" required inputmode="url" autocapitalize="none">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="clientsite" maxlength="16" required autocapitalize="none">
                        <div class="form-text">Lowercase, starts with a letter, max 16.</div>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Package</label>
                        <select name="plan" class="form-select" required>
                            <option value="">— choose —</option>
                            <?php foreach ($packages as $p): ?><option value="<?= e($p) ?>"><?= e($p) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label">Client <span class="text-muted small fw-normal">(optional)</span></label>
                        <select name="client_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['business_name']) ?></option><?php endforeach; ?>
                        </select>
                        <div class="form-text">Uses their email as the cPanel contact.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-brand"><i class="bi bi-hdd-network"></i> Create on <?= e($server) ?></button>
                    <span class="text-muted small ms-2">No password is set or stored — access is via the one-time cPanel login button.</span>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Hosting Accounts <span class="text-muted">(<?= count($accounts) ?>)</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Domain</th><th>Username</th><th>Plan</th><th>Disk</th><th>Status</th><th>Client</th><th>Synced</th><th class="text-end">Manage</th></tr></thead>
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
                        <td class="text-nowrap">
                            <span class="badge <?= $a['status'] === 'active' ? 'text-bg-success' : ($a['status'] === 'suspended' ? 'text-bg-danger' : 'text-bg-secondary') ?>"><?= e(ucfirst($a['status'])) ?></span>
                            <?php if ($a['status'] === 'suspended'): ?>
                                <form method="post" action="<?= route('admin.hosting.unsuspend', ['id' => $a['id']]) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-link text-success p-0 ms-1">Reactivate</button></form>
                            <?php else: ?>
                                <form method="post" action="<?= route('admin.hosting.suspend', ['id' => $a['id']]) ?>" class="d-inline" onsubmit="return confirm('Suspend hosting for <?= e($a['domain']) ?>?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0 ms-1">Suspend</button></form>
                            <?php endif; ?>
                        </td>
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
                        <td class="text-end">
                            <?php if ($a['status'] !== 'terminated'): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Manage</button>
                                    <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px">
                                        <?php if ($connected && $packages !== []): ?>
                                            <form method="post" action="<?= route('admin.hosting.package', ['id' => $a['id']]) ?>" class="mb-3">
                                                <?= csrf_field() ?>
                                                <label class="form-label small fw-semibold mb-1">Change package</label>
                                                <div class="d-flex gap-1">
                                                    <select name="plan" class="form-select form-select-sm">
                                                        <?php foreach ($packages as $p): ?><option value="<?= e($p) ?>" <?= $a['plan'] === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?>
                                                    </select>
                                                    <button class="btn btn-sm btn-outline-brand">Go</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= route('admin.hosting.password', ['id' => $a['id']]) ?>" class="mb-3">
                                            <?= csrf_field() ?>
                                            <label class="form-label small fw-semibold mb-1">New cPanel password</label>
                                            <div class="d-flex gap-1">
                                                <input type="password" name="password" class="form-control form-control-sm" minlength="12" autocomplete="new-password" placeholder="min 12 characters">
                                                <button class="btn btn-sm btn-outline-brand">Set</button>
                                            </div>
                                        </form>
                                        <form method="post" action="<?= route('admin.hosting.terminate', ['id' => $a['id']]) ?>" class="border-top pt-2">
                                            <?= csrf_field() ?>
                                            <label class="form-label small fw-semibold text-danger mb-1">Terminate — deletes the site and its mail</label>
                                            <div class="d-flex gap-1">
                                                <input type="text" name="confirm_domain" class="form-control form-control-sm" placeholder="type <?= e($a['domain']) ?>" autocomplete="off">
                                                <button class="btn btn-sm btn-outline-danger">Kill</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($accounts === []): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-hdd-network fs-3 d-block mb-2 opacity-50"></i>
                            No hosting accounts yet.
                            <?php if ($connected): ?>
                                <div class="small mt-1 mb-3">Accounts are imported from your WHM server, never created here. Sync to pull them in, then assign each one to a client so it shows in their portal.</div>
                                <form method="post" action="<?= route('admin.hosting.sync') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-brand"><i class="bi bi-arrow-repeat"></i> Sync Accounts Now</button>
                                </form>
                            <?php else: ?>
                                <div class="small mt-1">Accounts are imported from your WHM server. Add <code>WHM_HOST</code>, <code>WHM_USERNAME</code> and <code>WHM_API_TOKEN</code> to your <code>.env</code>, then sync — until then this screen stays empty and suspend/reactivate is recorded locally only.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
