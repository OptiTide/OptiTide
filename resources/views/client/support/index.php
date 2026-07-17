<?php
$this->extends('layouts.portal');
$badge = ['open' => 'text-bg-primary', 'pending' => 'text-bg-warning', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Need a hand? Log a request and our team will help. Everything you send us — and everything we send back — stays on this page.</div>
    <a href="<?= route('portal.support.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Request</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="d-none d-sm-table-cell">Ref</th><th>Subject</th><th class="d-none d-md-table-cell">Category</th><th>Status</th><th class="d-none d-sm-table-cell">Last Update</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <?php $href = route('portal.support.show', ['id' => $t['id']]); ?>
                    <tr onclick="window.location='<?= $href ?>'" style="cursor:pointer">
                        <td class="text-muted small d-none d-sm-table-cell"><?= e($t['number']) ?></td>
                        <?php // A real anchor carries the navigation — the row's onclick is only a
                              // convenience on top. Without the anchor the row is unreachable by
                              // keyboard and reads as plain text to a screen reader. ?>
                        <td class="fw-semibold"><a href="<?= $href ?>" class="text-reset text-decoration-none"><?= e($t['subject']) ?></a></td>
                        <td class="d-none d-md-table-cell"><?= $t['category'] ? '<span class="badge badge-soft">' . e($t['category']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge <?= $badge[$t['status']] ?? 'badge-soft' ?>"><?= e(\App\Models\Ticket::CLIENT_STATUSES[$t['status']] ?? ucfirst($t['status'])) ?></span></td>
                        <td class="text-nowrap text-muted small d-none d-sm-table-cell"><?= e($t['last_reply_at'] ? date('j M Y', strtotime($t['last_reply_at'])) : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($tickets === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-life-preserver fs-3 d-block mb-2"></i>
                            <div class="fw-semibold text-body">No Requests Yet</div>
                            <p class="mb-3">Got a question, a change you'd like made, or something not working? Log it here and we'll pick it up. You'll get an email when we reply.</p>
                            <a href="<?= route('portal.support.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> Open a Request</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
