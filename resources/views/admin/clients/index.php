<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="get" class="d-flex gap-2" style="max-width:340px">
        <input type="search" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Search clients…">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
    <a href="<?= route('admin.clients.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New client</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Business</th><th>Contact</th><th>Email</th><th>Status</th><th class="text-end">Outstanding</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (! $clients): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No clients yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($client['business_name']) ?></td>
                        <td><?= e($client['contact_name'] ?: '—') ?></td>
                        <td><?= e($client['email'] ?: '—') ?></td>
                        <td>
                            <span class="badge <?= $client['status'] === 'active' ? 'text-bg-success' : 'badge-soft' ?>"><?= e(ucfirst($client['status'])) ?></span>
                        </td>
                        <td class="text-end money"><?= e(money((int) ($balances[$client['id']] ?? 0), $currency)->format()) ?></td>
                        <td class="text-end"><a href="<?= route('admin.clients.show', ['id' => $client['id']]) ?>" class="btn btn-sm btn-light">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
