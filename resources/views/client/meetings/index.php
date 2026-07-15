<?php
$this->extends('layouts.portal');
$badge = ['scheduled' => 'text-bg-primary', 'completed' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">Your scheduled calls and meetings with our team.</p>

<?php if (! $meetings): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">No meetings scheduled. We'll invite you here when one is booked.</div></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($meetings as $m): ?>
        <?php $isUpcoming = $m['status'] === 'scheduled' && strtotime($m['meeting_at']) >= strtotime('today'); ?>
        <div class="col-md-6">
            <div class="card h-100 <?= $isUpcoming ? 'border-brand' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="fw-bold"><?= e($m['title']) ?></div>
                        <span class="badge <?= $badge[$m['status']] ?? 'badge-soft' ?>"><?= e(ucfirst($m['status'])) ?></span>
                    </div>
                    <div class="text-muted my-2"><i class="bi bi-calendar-event"></i> <?= e(date('l j F Y, g:ia', strtotime($m['meeting_at']))) ?></div>
                    <?php if (! empty($m['description'])): ?><p class="small text-muted mb-2"><?= nl2br(e($m['description'])) ?></p><?php endif; ?>
                    <?php if ($isUpcoming && ! empty($m['location']) && str_starts_with((string) $m['location'], 'http')): ?>
                        <a href="<?= e($m['location']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-brand"><i class="bi bi-camera-video"></i> Join meeting</a>
                    <?php elseif (! empty($m['location'])): ?>
                        <div class="small"><i class="bi bi-geo-alt"></i> <?= e($m['location']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>
