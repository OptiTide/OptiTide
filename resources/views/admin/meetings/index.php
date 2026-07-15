<?php
$this->extends('layouts.admin');
$badge = ['scheduled' => 'text-bg-primary', 'completed' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Schedule a meeting</div>
            <div class="card-body">
                <form method="post" action="<?= route('admin.meetings.store') ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select <?= has_error('client_id') ? 'is-invalid' : '' ?>" required>
                            <option value="">Choose…</option>
                            <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['business_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="<?= e(old('title')) ?>" class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" maxlength="160" required placeholder="Kickoff call">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">When</label>
                        <input type="datetime-local" name="meeting_at" value="<?= e(old('meeting_at')) ?>" class="form-control <?= has_error('meeting_at') ? 'is-invalid' : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zoom / meeting link</label>
                        <input type="text" name="location" value="<?= e(old('location')) ?>" class="form-control" placeholder="https://zoom.us/j/… (or a place)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                        <textarea name="description" rows="2" class="form-control" maxlength="1000"><?= e(old('description')) ?></textarea>
                    </div>
                    <button class="btn btn-brand"><i class="bi bi-calendar-plus"></i> Schedule &amp; invite</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Meetings</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>When</th><th>Client</th><th>Title</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($meetings as $m): ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d M Y, g:ia', strtotime($m['meeting_at']))) ?></td>
                                <td><?= e($client_names[$m['client_id']] ?? '—') ?></td>
                                <td class="fw-semibold"><?= e($m['title']) ?><?php if (! empty($m['location']) && str_starts_with((string) $m['location'], 'http')): ?> <a href="<?= e($m['location']) ?>" target="_blank" class="small">link</a><?php endif; ?></td>
                                <td><span class="badge <?= $badge[$m['status']] ?? 'badge-soft' ?>"><?= e(ucfirst($m['status'])) ?></span></td>
                                <td class="text-end">
                                    <?php if ($m['status'] === 'scheduled'): ?>
                                        <form method="post" action="<?= route('admin.meetings.cancel', ['id' => $m['id']]) ?>" onsubmit="return confirm('Cancel this meeting?')"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger">Cancel</button></form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($meetings === []): ?><tr><td colspan="5" class="text-center text-muted py-4">No meetings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
