<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\JobOpening;

$isEdit = $role !== null;
$action = $isEdit ? route('admin.careers.update', ['id' => $role['id']]) : route('admin.careers.store');

// Cents in the DB, dollars in the form — the house money rule.
$dollars = fn (?int $c) => $c === null ? '' : rtrim(rtrim(number_format($c / 100, 2, '.', ''), '0'), '.');
?>

<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?><?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">The Role</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Job title</label>
                        <input type="text" name="title" value="<?= e(old('title', $role['title'] ?? '')) ?>" class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" placeholder="e.g. Front-End Web Developer" required>
                        <?php if (error('title')): ?><div class="invalid-feedback"><?= e(error('title')) ?></div><?php endif; ?>
                        <?php if ($isEdit): ?><div class="form-text">URL: /careers/<?= e($role['slug']) ?> — renaming the title changes this link.</div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">One-line summary <span class="text-muted small">— shown on the careers list and in search results</span></label>
                        <input type="text" name="summary" value="<?= e(old('summary', $role['summary'] ?? '')) ?>" maxlength="400" class="form-control" placeholder="Build fast, beautiful sites for Australian small business.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">About the role</label>
                        <textarea name="description" rows="7" class="form-control <?= has_error('description') ? 'is-invalid' : '' ?>" required placeholder="What the job actually is, who they'll work with, what a normal week looks like."><?= e(old('description', $role['description'] ?? '')) ?></textarea>
                        <?php if (error('description')): ?><div class="invalid-feedback"><?= e(error('description')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">What they'll do <span class="text-muted small">— one per line</span></label>
                        <textarea name="responsibilities" rows="5" class="form-control" placeholder="Build responsive sites in HTML/CSS/JS&#10;Turn designs into working pages&#10;Keep sites fast and accessible"><?= e(old('responsibilities', $role['responsibilities'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">What we're after <span class="text-muted small">— one per line</span></label>
                        <textarea name="requirements" rows="5" class="form-control" placeholder="2+ years building websites&#10;Strong CSS fundamentals&#10;Australian work rights"><?= e(old('requirements', $role['requirements'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">What they'll get <span class="text-muted small">— one per line, optional</span></label>
                        <textarea name="benefits" rows="4" class="form-control" placeholder="Fully remote&#10;Flexible hours&#10;Training budget"><?= e(old('benefits', $role['benefits'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Publishing</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (JobOpening::STATUSES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= old('status', $role['status'] ?? 'draft') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only <strong>Open</strong> roles appear on the site and in Google. Draft and Closed stay hidden.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Applications close <span class="text-muted small">(optional)</span></label>
                        <input type="date" name="closes_at" value="<?= e(old('closes_at', ! empty($role['closes_at']) ? substr((string) $role['closes_at'], 0, 10) : '')) ?>" class="form-control <?= has_error('closes_at') ? 'is-invalid' : '' ?>">
                        <div class="form-text">After this date the role hides itself automatically.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" value="<?= e(old('sort_order', (string) ($role['sort_order'] ?? 0))) ?>" class="form-control">
                        <div class="form-text">Lower numbers appear first.</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Where &amp; How</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Team / department <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="department" value="<?= e(old('department', $role['department'] ?? '')) ?>" class="form-control" placeholder="e.g. Web Design">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="<?= e(old('location', $role['location'] ?? 'Australia — Remote')) ?>" class="form-control <?= has_error('location') ? 'is-invalid' : '' ?>" required>
                        <?php if (error('location')): ?><div class="invalid-feedback"><?= e(error('location')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employment type</label>
                        <select name="employment_type" class="form-select">
                            <?php foreach (JobOpening::EMPLOYMENT_TYPES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= old('employment_type', $role['employment_type'] ?? 'full_time') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Workplace</label>
                        <select name="workplace_type" class="form-select">
                            <?php foreach (JobOpening::WORKPLACE_TYPES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= old('workplace_type', $role['workplace_type'] ?? 'remote') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Salary</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="salvis" name="salary_visible" value="1" <?= old('salary_visible', ! empty($role['salary_visible']) ? '1' : '') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="salvis">Show salary publicly</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Min</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="salary_min" value="<?= e(old('salary_min', $dollars($role['salary_min_cents'] ?? null))) ?>" class="form-control" placeholder="90000">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Max</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="salary_max" value="<?= e(old('salary_max', $dollars($role['salary_max_cents'] ?? null))) ?>" class="form-control" placeholder="110000">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Period</label>
                            <select name="salary_period" class="form-select">
                                <?php foreach (JobOpening::SALARY_PERIODS as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= old('salary_period', $role['salary_period'] ?? 'year') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-text mt-2">In <?= e(config('company.currency') ?: 'AUD') ?>. Listing a real range gets noticeably more applicants — but leave the switch off to keep it internal.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-1 d-flex gap-2">
        <button class="btn btn-brand btn-lg"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save Role' : 'Create Role' ?></button>
        <a href="<?= route('admin.careers.index') ?>" class="btn btn-lg btn-link">Cancel</a>
    </div>
</form>
<?php $this->endSection(); ?>
