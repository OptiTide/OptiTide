<?php
$this->extends('layouts.admin');
$badge = ['open' => 'text-bg-success', 'closed' => 'text-bg-secondary'];
?>
<?php $this->section('content'); ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Visitor</th><th>Handled By</th><th>Status</th><th>Last Activity</th></tr></thead>
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
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-chat-dots fs-3 d-block mb-2 opacity-50"></i>
                            No chats yet.
                            <div class="small mt-1 mb-3">
                                Conversations start when a visitor opens the chat bubble on your site
                                <?php if (\App\Support\Features::enabled('ai_chat')): ?>
                                    — the assistant answers straight away, and you can take over any thread at any time.
                                <?php else: ?>
                                    — AI replies are switched off, so every chat waits for your team. Keep an eye on this screen.
                                <?php endif; ?>
                            </div>
                            <a href="<?= route('home') ?>" target="_blank" class="btn btn-sm btn-outline-brand"><i class="bi bi-box-arrow-up-right"></i> Try the Widget</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2"><i class="bi bi-arrow-clockwise"></i> Refreshes automatically.</p>
<script>setTimeout(function(){location.reload();}, 20000);</script>
<?php $this->endSection(); ?>
