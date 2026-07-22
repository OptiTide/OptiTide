<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<a href="<?= route('admin.emails.index') ?>" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Back to email log</a>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <h1 class="h5 mb-0"><?= e($row['subject'] ?: '(no subject)') ?></h1>
    <?php if ($row['status'] === 'sent'): ?>
        <span class="badge text-bg-success-subtle text-success-emphasis">Sent</span>
    <?php elseif ($row['status'] === 'failed'): ?>
        <span class="badge text-bg-danger-subtle text-danger-emphasis">Failed</span>
    <?php else: ?>
        <span class="badge text-bg-warning-subtle text-warning-emphasis">Unfinished</span>
    <?php endif; ?>
</div>

<?php if ($row['status'] === 'failed'): ?>
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">This email was not sent.</div>
        <div class="small"><?= e($row['error'] ?: 'No detail recorded.') ?></div>
    </div>
<?php elseif ($row['status'] === 'sending'): ?>
    <div class="alert alert-warning">
        <div class="fw-semibold mb-1">This send never finished.</div>
        <div class="small">The attempt was recorded but no result came back — usually the process was interrupted (a deploy, a timeout, a killed worker) part-way through. Whether it reached the recipient is unknown; check with the mail provider before re-sending.</div>
    </div>
<?php endif; ?>

<div class="card mb-3"><div class="card-body">
    <dl class="row mb-0 small">
        <dt class="col-sm-3 text-muted">Sent</dt>
        <dd class="col-sm-9"><?= e(date('d M Y, H:i:s', strtotime((string) $row['created_at']))) ?></dd>

        <dt class="col-sm-3 text-muted">To</dt>
        <dd class="col-sm-9"><?= e($row['to_name'] ? $row['to_name'] . ' <' . $row['to_email'] . '>' : $row['to_email']) ?></dd>

        <dt class="col-sm-3 text-muted">From</dt>
        <dd class="col-sm-9"><?= e($row['from_email'] ?: '—') ?></dd>

        <?php if (! empty($row['reply_to'])): ?>
            <dt class="col-sm-3 text-muted">Reply-to</dt>
            <dd class="col-sm-9"><?= e($row['reply_to']) ?></dd>
        <?php endif; ?>

        <dt class="col-sm-3 text-muted">Delivered via</dt>
        <dd class="col-sm-9"><?= e($row['mailer'] ?: '—') ?></dd>

        <?php if (! empty($row['provider_message_id'])): ?>
            <dt class="col-sm-3 text-muted">Provider ID</dt>
            <dd class="col-sm-9"><code class="small"><?= e($row['provider_message_id']) ?></code>
                <div class="text-muted">Use this to find the message in your mail provider's dashboard.</div></dd>
        <?php endif; ?>

        <?php if ($attachments): ?>
            <dt class="col-sm-3 text-muted">Attachments</dt>
            <dd class="col-sm-9">
                <?php foreach ($attachments as $a): ?>
                    <div><i class="bi bi-paperclip"></i> <?= e($a['filename'] ?? '') ?>
                        <span class="text-muted">(<?= number_format(((int) ($a['bytes'] ?? 0)) / 1024, 1) ?> KB)</span></div>
                <?php endforeach; ?>
                <div class="text-muted mt-1">File names only — attachment contents are not stored.</div>
            </dd>
        <?php endif; ?>
    </dl>
</div></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Message</span>
        <?php if (! empty($row['body_html'])): ?>
            <a href="<?= route('admin.emails.body', ['id' => $row['id']]) ?>" target="_blank" rel="noopener" class="small">Open in new tab</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($row['body_html'])): ?>
            <p class="text-muted small m-3 mb-0">The message body has been cleared by the retention policy. The record of who was emailed, when, and whether it sent is kept.</p>
        <?php else: ?>
            <?php /* Sandboxed: no scripts, no same-origin. The body interpolates
                     client-supplied values, so it is never inlined into this page. */ ?>
            <iframe src="<?= route('admin.emails.body', ['id' => $row['id']]) ?>"
                    sandbox
                    referrerpolicy="no-referrer"
                    style="width:100%;height:60vh;border:0;background:#fff"
                    title="Email body"></iframe>
        <?php endif; ?>
    </div>
</div>

<p class="text-muted small mt-2">
    Password-reset and email-verification links are stored with the token removed, so this copy cannot be used to take over an account.
</p>

<?php $this->endSection(); ?>
