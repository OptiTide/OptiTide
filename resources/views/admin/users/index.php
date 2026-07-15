<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= route('admin.users.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New User</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Client</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><span class="badge <?= $user['role'] === 'admin' ? 'text-bg-dark' : ($user['role'] === 'staff' ? 'text-bg-info' : 'badge-soft') ?>"><?= e(\App\Models\User::ROLES[$user['role']] ?? $user['role']) ?></span></td>
                        <td><?= e($user['client_id'] ? ($client_names[$user['client_id']] ?? '—') : '—') ?></td>
                        <td><span class="badge <?= ($user['status'] ?? 'active') === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($user['status'] ?? 'active')) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= route('admin.users.edit', ['id' => $user['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                            <?php if ((string) $user['id'] !== (string) auth()['id']): ?>
                                <form method="post" action="<?= route('admin.users.destroy', ['id' => $user['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                    <?= csrf_field() ?><?= method_field('DELETE') ?>
                                    <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
