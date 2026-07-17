<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php use App\Models\JobApplication; ?>
<?php
// "general" is a filter value, not a role id — the controller maps it to
// "no role attached", which we then apply here.
$rows = $applications;
if ($onlyGeneral) {
    $rows = array_values(array_filter($rows, fn ($a) => empty($a['job_opening_id'])));
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted"><?= count($rows) ?> application<?= count($rows) === 1 ? '' : 's' ?></div>
    <a href="<?= route('admin.careers.index') ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-briefcase"></i> Manage Roles</a>
</div>

<form method="get" action="<?= route('admin.careers.applications') ?>" class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Role</label>
            <select name="role" class="form-select form-select-sm" style="min-width:14rem">
                <option value="">All roles</option>
                <option value="general" <?= $activeRole === 'general' ? 'selected' : '' ?>>General applications</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= $activeRole === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm" style="min-width:11rem">
                <option value="">Any status</option>
                <?php foreach (JobApplication::STATUSES as $k => $v): ?>
                    <option value="<?= e($k) ?>" <?= $activeStatus === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-sm btn-brand">Filter</button>
        <?php if ($activeRole !== '' || $activeStatus !== ''): ?>
            <a href="<?= route('admin.careers.applications') ?>" class="btn btn-sm btn-link">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Applicant</th><th>Role</th><th>CV</th><th>Status</th><th>Received</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rows as $a): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= e($a['name']) ?>
                            <div class="text-muted small"><?= e($a['email']) ?><?= $a['location'] ? ' · ' . e($a['location']) : '' ?></div>
                        </td>
                        <td class="small">
                            <?= e($a['role_title']) ?>
                            <?php if (empty($a['job_opening_id'])): ?>
                                <div class="text-muted"><i class="bi bi-info-circle"></i> not tied to a live role</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($a['resume_path'])): ?>
                                <a href="<?= route('admin.careers.applications.resume', ['id' => $a['id']]) ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-paperclip"></i> CV</a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= e(JobApplication::STATUS_BADGES[$a['status']] ?? 'text-bg-secondary') ?>"><?= e(JobApplication::STATUSES[$a['status']] ?? $a['status']) ?></span></td>
                        <td class="text-nowrap small"><?= e(date('d M Y', strtotime((string) $a['created_at']))) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= route('admin.careers.application', ['id' => $a['id']]) ?>" class="btn btn-sm btn-link">Review <i class="bi bi-arrow-right"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                            <?php if ($activeRole !== '' || $activeStatus !== ''): ?>
                                No applications match that filter.
                                <div class="mt-2"><a href="<?= route('admin.careers.applications') ?>" class="btn btn-sm btn-light">Show All Applications</a></div>
                            <?php else: ?>
                                No applications yet.
                                <div class="small mt-1 mb-3">People apply from your public careers page — you can't add an application here. Post a role to start collecting them; until you do, the page invites general expressions of interest.</div>
                                <a href="<?= route('admin.careers.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> Post a Role</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
