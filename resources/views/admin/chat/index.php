<?php
$this->extends('layouts.admin');
$badge = ['open' => 'text-bg-success', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Visitor</th><th>Handled by</th><th>Status</th><th>Last activity</th></tr></thead>
            <tbody>
                <?php foreach ($conversations as $c): ?>
                    <tr onclick="window.location='<?= route('admin.chat.show', ['id' => $c['id']]) ?>'" style="cursor:pointer">
                        <td class="fw-semibold"><?= e($c['name'] ?: 'Visitor') ?><?php if (! empty($c['email'])): ?><div class="text-muted small"><?= e($c['email']) ?></div><?php endif; ?></td>
                        <td><?= $c['mode'] === 'human' ? '<span class="badge text-bg-primary">You / team</span>' : '<span class="badge badge-soft">Assistant</span>' ?></td>
                        <td><span class="badge <?= $badge[$c['status']] ?? 'badge-soft' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
                        <td class="text-nowrap text-muted small"><?= e($c['last_message_at'] ? date('d M, g:ia', strtotime($c['last_message_at'])) : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($conversations === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No chats yet. The widget answers visitors instantly; you'll see conversations here.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-arrow-clockwise"></i> Refreshes automatically.</p>
<script>setTimeout(function(){location.reload();}, 20000);</script>
<?php $this->endSection(); ?>
