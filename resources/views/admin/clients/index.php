<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="search" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" style="max-width:240px" placeholder="Search clients…">
        <select name="status" class="form-select form-select-sm" style="max-width:150px" onchange="this.form.submit()">
            <option value="" <?= $status === '' ? 'selected' : '' ?>>All Statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
    <a href="<?= route('admin.clients.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Client</a>
</div>

<p class="text-muted small mb-3">
    <strong><?= count($clients) ?></strong> <?= count($clients) === 1 ? 'client' : 'clients' ?>
    <?php if ($status !== ''): ?>(<?= e(ucfirst($status)) ?>)<?php endif ?>
    &middot; <strong class="text-brand"><?= e(money((int) $outstandingTotal, $currency)->format()) ?></strong> outstanding
</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>ABN</th>
                    <th>Contact</th>
                    <th>E-Mail</th>
                    <th>Status</th>
                    <th class="text-end">Outstanding</th>
                    <th class="text-end">Total Paid</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (! $clients): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-3 d-block mb-2 opacity-50"></i>
                            <?php if ($search !== '' || $status !== ''): ?>
                                No clients match your filters.
                                <a href="<?= route('admin.clients.index') ?>" class="text-brand">Clear</a>
                            <?php else: ?>
                                No clients yet.
                                <a href="<?= route('admin.clients.create') ?>" class="text-brand">Add your first client</a>.
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($client['business_name']) ?></td>
                        <td class="text-muted small"><?= e($client['abn'] ?: '—') ?></td>
                        <td><?= e($client['contact_name'] ?: '—') ?></td>
                        <td><?= e($client['email'] ?: '—') ?></td>
                        <td>
                            <?php $stBadge = ['active' => 'text-bg-success', 'suspended' => 'text-bg-danger', 'archived' => 'text-bg-secondary']; ?>
                            <span class="badge <?= $stBadge[$client['status']] ?? 'badge-soft' ?>"><?= e(ucfirst($client['status'])) ?></span>
                            <?php if (! empty($overdue[$client['id']])): ?><span class="badge text-bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i> Overdue</span><?php endif; ?>
                        </td>
                        <td class="text-end money"><?= e(money((int) ($balances[$client['id']] ?? 0), $currency)->format()) ?></td>
                        <td class="text-end money text-muted"><?= e(money((int) ($paid[$client['id']] ?? 0), $currency)->format()) ?></td>
                        <td class="text-end"><a href="<?= route('admin.clients.show', ['id' => $client['id']]) ?>" class="btn btn-sm btn-light">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
