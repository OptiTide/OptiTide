<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Email Log</h1>
        <div class="text-muted small">Every email the system has tried to send, newest first.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge text-bg-success-subtle text-success-emphasis"><?= (int) ($counts['sent'] ?? 0) ?> sent</span>
        <span class="badge text-bg-danger-subtle text-danger-emphasis"><?= (int) ($counts['failed'] ?? 0) ?> failed</span>
        <?php if (! empty($counts['sending'])): ?>
            <span class="badge text-bg-warning-subtle text-warning-emphasis" title="Started but never finished — the process was interrupted mid-send."><?= (int) $counts['sending'] ?> unfinished</span>
        <?php endif; ?>
    </div>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-sm-6 col-lg-4">
        <label class="form-label small text-muted mb-1">Search</label>
        <input type="search" name="search" value="<?= e($f_search) ?>" class="form-control" placeholder="Recipient or subject">
    </div>
    <div class="col-sm-4 col-lg-3">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select">
            <option value="">All</option>
            <?php foreach (['sent' => 'Sent', 'failed' => 'Failed', 'sending' => 'Unfinished'] as $v => $label): ?>
                <option value="<?= e($v) ?>"<?= $f_status === $v ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Filter</button>
        <?php if ($f_search !== '' || $f_status !== ''): ?>
            <a href="<?= route('admin.emails.index') ?>" class="btn btn-link">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (! $rows): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-envelope fs-2 d-block mb-2"></i>
        <?= $f_search !== '' || $f_status !== '' ? 'No emails match that filter.' : 'No emails sent yet.' ?>
    </div></div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>Sent</th><th>To</th><th>Subject</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-nowrap small text-muted"><?= e(date('d M Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td>
                            <div class="text-truncate" style="max-width:220px"><?= e($r['to_email']) ?></div>
                            <?php if (! empty($r['to_name'])): ?><div class="small text-muted text-truncate" style="max-width:220px"><?= e($r['to_name']) ?></div><?php endif; ?>
                        </td>
                        <td><div class="text-truncate" style="max-width:320px"><?= e($r['subject'] ?: '(no subject)') ?></div></td>
                        <td>
                            <?php if ($r['status'] === 'sent'): ?>
                                <span class="badge text-bg-success-subtle text-success-emphasis">Sent</span>
                            <?php elseif ($r['status'] === 'failed'): ?>
                                <span class="badge text-bg-danger-subtle text-danger-emphasis">Failed</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning-subtle text-warning-emphasis">Unfinished</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><a href="<?= route('admin.emails.show', ['id' => $r['id']]) ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-3"><ul class="pagination pagination-sm">
            <?php for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++): ?>
                <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($f_search) ?>&status=<?= urlencode($f_status) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    <?php endif; ?>

    <p class="text-muted small mt-2"><?= number_format($total) ?> email<?= $total === 1 ? '' : 's' ?> recorded.</p>
<?php endif; ?>

<?php $this->endSection(); ?>
