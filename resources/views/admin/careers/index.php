<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php use App\Models\JobOpening; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted"><?= count($roles) ?> role<?= count($roles) === 1 ? '' : 's' ?></div>
    <div class="d-flex gap-2">
        <a href="<?= route('admin.careers.applications') ?>" class="btn btn-sm btn-outline-brand">
            <i class="bi bi-inbox"></i> Applications
            <?php if ($newCount > 0): ?><span class="badge text-bg-primary ms-1"><?= (int) $newCount ?> new</span><?php endif; ?>
        </a>
        <a href="<?= route('careers.index') ?>" target="_blank" class="btn btn-sm btn-outline-brand"><i class="bi bi-box-arrow-up-right"></i> View Careers Page</a>
        <a href="<?= route('admin.careers.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Role</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Role</th><th>Location</th><th>Type</th><th>Status</th><th class="text-end">Applications</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= e($r['title']) ?>
                            <div class="text-muted small">
                                /careers/<?= e($r['slug']) ?><?= $r['department'] ? ' · ' . e($r['department']) : '' ?>
                            </div>
                        </td>
                        <td class="small"><?= e($r['location']) ?><div class="text-muted"><?= e(JobOpening::WORKPLACE_TYPES[$r['workplace_type']] ?? '') ?></div></td>
                        <td class="small"><?= e(JobOpening::EMPLOYMENT_TYPES[$r['employment_type']] ?? '') ?></td>
                        <td>
                            <?php
                            $badge = match ($r['status']) {
                                JobOpening::STATUS_OPEN   => 'text-bg-success',
                                JobOpening::STATUS_CLOSED => 'text-bg-dark',
                                default                   => 'text-bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= e(JobOpening::STATUSES[$r['status']] ?? $r['status']) ?></span>
                            <?php if (JobOpening::hasClosed($r) && $r['status'] === JobOpening::STATUS_OPEN): ?>
                                <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle"></i> Past close date — hidden</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php $n = (int) ($counts[$r['id']] ?? 0); ?>
                            <?php if ($n > 0): ?>
                                <a href="<?= route('admin.careers.applications') ?>?role=<?= (int) $r['id'] ?>"><?= $n ?></a>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($r['status'] === JobOpening::STATUS_OPEN): ?>
                                <a href="<?= route('careers.show', ['slug' => $r['slug']]) ?>" target="_blank" class="btn btn-sm btn-link" title="View"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                            <a href="<?= route('admin.careers.edit', ['id' => $r['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= route('admin.careers.destroy', ['id' => $r['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this role? Applications for it are kept.')">
                                <?= csrf_field() ?><?= method_field('DELETE') ?>
                                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($roles === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        No roles yet. <a href="<?= route('admin.careers.create') ?>">Post your first one.</a>
                        <div class="small mt-2">Until then the careers page invites general expressions of interest.</div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($generalCount > 0): ?>
    <p class="text-muted small mt-3">
        <i class="bi bi-info-circle"></i>
        <a href="<?= route('admin.careers.applications') ?>?role=general"><?= (int) $generalCount ?> general application<?= $generalCount === 1 ? '' : 's' ?></a>
        not tied to a specific role.
    </p>
<?php endif; ?>
<?php $this->endSection(); ?>
