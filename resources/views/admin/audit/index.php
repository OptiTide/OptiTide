<?php
$this->extends('layouts.admin');

// Colour-code the verb family for quick scanning.
$family = function (string $action): string {
    $head = explode('.', $action)[0];
    return [
        'user'       => 'text-bg-secondary',
        'auth'       => 'text-bg-secondary',
        'invoice'    => 'text-bg-primary',
        'payment'    => 'text-bg-success',
        'credit'     => 'text-bg-info',
        'commission' => 'text-bg-warning',
        'order'      => 'text-bg-primary',
        'hosting'    => 'text-bg-dark',
        'meeting'    => 'text-bg-info',
        'client'     => 'text-bg-secondary',
    ][$head] ?? 'text-bg-light';
};
?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">A tamper-evident record of every significant action across the platform — <?= number_format($total) ?> event<?= $total === 1 ? '' : 's' ?>.</p>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-sm-4 col-md-3">
        <label class="form-label small mb-1">Action</label>
        <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= e($a) ?>" <?= $f_action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-md-3">
        <label class="form-label small mb-1">Staff member</label>
        <select name="actor" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Anyone</option>
            <?php foreach ($staff as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (string) $f_actor === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($f_action !== '' || $f_actor !== ''): ?>
        <div class="col-auto"><a href="<?= route('admin.audit.index') ?>" class="btn btn-sm btn-link">Clear</a></div>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-nowrap small text-muted"><?= e(date('d M Y, H:i:s', strtotime($r['created_at']))) ?></td>
                        <td class="small"><?= e($r['actor_name'] ?: 'System') ?></td>
                        <td><span class="badge <?= $family($r['action']) ?>"><?= e($r['action']) ?></span></td>
                        <td class="small text-muted"><?= $r['subject_type'] ? e($r['subject_type']) . ' #' . e($r['subject_id']) : '—' ?></td>
                        <td class="small">
                            <?php $meta = $r['meta'] ? json_decode($r['meta'], true) : null; ?>
                            <?php if (is_array($meta) && $meta): ?>
                                <?php foreach ($meta as $k => $v): ?><span class="text-muted"><?= e($k) ?>:</span> <?= e(is_scalar($v) ? (string) $v : json_encode($v)) ?><?= ! ($k === array_key_last($meta)) ? ' · ' : '' ?><?php endforeach; ?>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e($r['ip'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-journal-text fs-3 d-block mb-2 opacity-50"></i>
                            <?php if ($f_action !== '' || $f_actor !== ''): ?>
                                No events match this filter.
                                <div class="mt-2"><a href="<?= route('admin.audit.index') ?>" class="btn btn-sm btn-light">Show All Events</a></div>
                            <?php else: ?>
                                Nothing recorded yet.
                                <div class="small mt-1">Significant actions — issuing an invoice, waiving a fee, changing a user — are written here automatically as they happen. Entries can't be added or edited by hand.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3 d-flex justify-content-between align-items-center">
        <?php
        $link = function ($p) use ($f_action, $f_actor) {
            $q = array_filter(['action' => $f_action, 'actor' => $f_actor, 'page' => $p], fn ($v) => $v !== '' && $v !== null);
            return route('admin.audit.index') . '?' . http_build_query($q);
        };
        ?>
        <div><?= $page > 1 ? '<a class="btn btn-sm btn-outline-secondary" href="' . e($link($page - 1)) . '">&larr; Newer</a>' : '' ?></div>
        <div class="small text-muted">Page <?= $page ?> of <?= $pages ?></div>
        <div><?= $page < $pages ? '<a class="btn btn-sm btn-outline-secondary" href="' . e($link($page + 1)) . '">Older &rarr;</a>' : '' ?></div>
    </nav>
<?php endif; ?>
<?php $this->endSection(); ?>
