<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<?php
    $roleBadge = [
        'admin'  => 'text-bg-dark',
        'staff'  => 'text-bg-info',
        'client' => 'badge-soft',
    ];
    $filters = ['' => 'All Users'] + \App\Models\User::ROLES;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group" role="group" aria-label="Filter By Role">
        <?php foreach ($filters as $key => $label): ?>
            <a href="<?= route('admin.users.index') ?><?= $key === '' ? '' : '?role=' . e($key) ?>"
               class="btn btn-sm <?= ($role ?? '') === $key ? 'btn-brand' : 'btn-outline-brand' ?>">
                <?= e($label) ?>
                <span class="badge text-bg-light ms-1"><?= (int) ($role_counts[$key] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <a href="<?= route('admin.users.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New User</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>E-Mail</th><th>Role</th><th>Client</th><th>2FA</th><th>Last Login</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><span class="badge <?= $roleBadge[$user['role']] ?? 'badge-soft' ?>"><?= e(\App\Models\User::ROLES[$user['role']] ?? $user['role']) ?></span></td>
                        <td><?= e($user['role'] === 'client' && $user['client_id'] ? ($client_names[$user['client_id']] ?? '—') : '—') ?></td>
                        <td>
                            <?php if (! empty($user['two_factor_confirmed_at'])): ?>
                                <span class="badge text-bg-success"><i class="bi bi-shield-check"></i> On</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Off</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= e(! empty($user['last_login_at']) ? date('d M Y', strtotime($user['last_login_at'])) : 'Never') ?></td>
                        <td><span class="badge <?= ($user['status'] ?? 'active') === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($user['status'] ?? 'active')) ?></span></td>
                        <td class="text-end text-nowrap">
                            <?php if ($user['role'] === \App\Models\User::ROLE_CLIENT && \App\Core\Auth::isAdmin()): ?>
                                <form method="post" action="<?= route('admin.users.loginas', ['id' => $user['id']]) ?>" class="d-inline" title="Log in as this client">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-link" onclick="return confirm('Log in as this client to preview their portal?')"><i class="bi bi-box-arrow-in-right"></i></button>
                                </form>
                            <?php endif; ?>
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
                <?php if ($users === []): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users match this filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
