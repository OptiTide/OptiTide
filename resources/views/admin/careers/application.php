<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\JobApplication;
use App\Support\Upload;

$a = $application;
?>

<div class="mb-3">
    <a href="<?= route('admin.careers.applications') ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-arrow-left"></i> All applications</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= e($a['name']) ?> — <?= e($a['role_title']) ?></span>
                <span class="badge <?= e(JobApplication::STATUS_BADGES[$a['status']] ?? 'text-bg-secondary') ?>"><?= e(JobApplication::STATUSES[$a['status']] ?? $a['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-2" style="letter-spacing:.05em">Cover note</div>
                <div style="white-space:pre-wrap;line-height:1.7"><?= e($a['cover_letter']) ?></div>
            </div>
        </div>

        <form method="post" action="<?= route('admin.careers.applications.update', ['id' => $a['id']]) ?>" class="card mb-3">
            <?= csrf_field() ?><?= method_field('PUT') ?>
            <div class="card-header">Review</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (JobApplication::STATUSES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Internal notes <span class="text-muted small">— never shown to the applicant</span></label>
                        <textarea name="staff_notes" rows="5" class="form-control" placeholder="Your read on this person, interview notes, next steps…"><?= e($a['staff_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <button class="btn btn-brand"><i class="bi bi-check-lg"></i> Save</button>
                <a href="mailto:<?= e(rawurlencode($a['email'])) ?>?subject=<?= rawurlencode('Your application — ' . $a['role_title']) ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-envelope"></i> Reply by email</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Applicant</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Name</dt><dd class="col-7"><?= e($a['name']) ?></dd>
                    <dt class="col-5 text-muted">Email</dt><dd class="col-7"><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd>
                    <?php if ($a['phone']): ?><dt class="col-5 text-muted">Phone</dt><dd class="col-7"><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $a['phone'])) ?>"><?= e($a['phone']) ?></a></dd><?php endif; ?>
                    <?php if ($a['location']): ?><dt class="col-5 text-muted">Based in</dt><dd class="col-7"><?= e($a['location']) ?></dd><?php endif; ?>
                    <dt class="col-5 text-muted">Applied</dt><dd class="col-7"><?= e(date('d M Y, g:ia', strtotime((string) $a['created_at']))) ?></dd>
                </dl>
                <?php if ($a['linkedin_url'] || $a['portfolio_url']): ?>
                    <hr>
                    <div class="d-grid gap-2">
                        <?php
                        // Applicant-supplied URLs: rel=noopener noreferrer + a scheme
                        // check, so a javascript:/data: link can never be clickable.
                        $safeLink = function (?string $url): ?string {
                            $url = trim((string) $url);
                            if ($url === '') {
                                return null;
                            }
                            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

                            return in_array($scheme, ['http', 'https'], true) ? $url : null;
                        };
                        ?>
                        <?php if ($li = $safeLink($a['linkedin_url'])): ?>
                            <a href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-sm btn-outline-brand"><i class="bi bi-linkedin"></i> LinkedIn</a>
                        <?php endif; ?>
                        <?php if ($pf = $safeLink($a['portfolio_url'])): ?>
                            <a href="<?= e($pf) ?>" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-sm btn-outline-brand"><i class="bi bi-globe"></i> Portfolio</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">CV</div>
            <div class="card-body">
                <?php if (! empty($a['resume_path'])): ?>
                    <p class="small mb-2"><i class="bi bi-file-earmark-text"></i> <?= e($a['resume_name']) ?>
                        <span class="text-muted"><?= e(Upload::sizeLabel($a['resume_size'] ?? null)) ?></span>
                    </p>
                    <a href="<?= route('admin.careers.applications.resume', ['id' => $a['id']]) ?>" class="btn btn-brand btn-sm w-100"><i class="bi bi-download"></i> Download CV</a>
                    <p class="form-text mb-0">Downloads are logged in the audit trail.</p>
                <?php else: ?>
                    <p class="text-muted small mb-0">No CV attached — check their portfolio or cover note.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-danger-subtle">
            <div class="card-body">
                <div class="small text-muted mb-2">Deleting removes the application <strong>and</strong> their CV from the server. Use this once you no longer need to keep their personal data.</div>
                <form method="post" action="<?= route('admin.careers.applications.destroy', ['id' => $a['id']]) ?>" onsubmit="return confirm('Permanently delete this application and their CV?')">
                    <?= csrf_field() ?><?= method_field('DELETE') ?>
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Delete application &amp; CV</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
