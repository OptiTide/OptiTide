<?php
$this->extends('layouts.portal');
$badge = ['requested' => 'text-bg-warning', 'scheduled' => 'text-bg-primary', 'completed' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'];
$statusLabel = ['requested' => 'Awaiting Confirmation'];
?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <p class="text-muted mb-0">Your calls and meetings with our team.</p>
    <button class="btn btn-brand btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#requestMeeting">
        <i class="bi bi-calendar-plus"></i> Request a Meeting
    </button>
</div>

<div class="collapse mb-4" id="requestMeeting">
    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Request a Meeting</h6>
            <p class="small text-muted">Pick a topic and a time that suits you. We'll confirm the time (with a video link) by email and here in your portal.</p>
            <form method="post" action="<?= route('portal.meetings.request') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">What's it about?</label>
                        <input type="text" name="title" class="form-control" maxlength="160" required placeholder="e.g. Website progress review">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Preferred date &amp; time</label>
                        <input type="datetime-local" name="meeting_at" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Anything we should prepare? <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="2" maxlength="1000" placeholder="Add any details or questions"></textarea>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-brand" type="submit"><i class="bi bi-send"></i> Send Request</button></div>
            </form>
        </div>
    </div>
</div>

<?php if (! $meetings): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">No meetings yet. Use <strong>Request a Meeting</strong> above to book a time, or we'll invite you here when one is scheduled.</div></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($meetings as $m): ?>
        <?php $isUpcoming = $m['status'] === 'scheduled' && strtotime($m['meeting_at']) >= strtotime('today'); ?>
        <div class="col-md-6">
            <div class="card h-100 <?= $isUpcoming ? 'border-brand' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="fw-bold"><?= e($m['title']) ?></div>
                        <span class="badge <?= $badge[$m['status']] ?? 'badge-soft' ?>"><?= e($statusLabel[$m['status']] ?? ucfirst($m['status'])) ?></span>
                    </div>
                    <div class="text-muted my-2"><i class="bi bi-calendar-event"></i> <?= e(date('l j F Y, g:ia', strtotime($m['meeting_at']))) ?></div>
                    <?php if (! empty($m['description'])): ?><p class="small text-muted mb-2"><?= nl2br(e($m['description'])) ?></p><?php endif; ?>
                    <?php if ($isUpcoming && ! empty($m['location']) && str_starts_with((string) $m['location'], 'http')): ?>
                        <a href="<?= e($m['location']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-brand"><i class="bi bi-camera-video"></i> Join Meeting</a>
                    <?php elseif (! empty($m['location'])): ?>
                        <div class="small"><i class="bi bi-geo-alt"></i> <?= e($m['location']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>
