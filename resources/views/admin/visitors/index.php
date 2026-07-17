<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value"><?= number_format($total) ?></div><div class="stat-label">Page Views</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value"><?= number_format($unique) ?></div><div class="stat-label">Unique Visitors</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card stat-card h-100"><div class="card-body"><div class="stat-value"><?= number_format($last7) ?></div><div class="stat-label">Views (7 Days)</div></div></div></div>
    <div class="col-md-3 col-6">
        <?php // The chat inbox 404s when live chat is off — don't offer the link then. ?>
        <?php $chatOn = \App\Support\Features::enabled('live_chat'); ?>
        <?php if ($chatOn): ?><a href="<?= route('admin.chat.index') ?>" class="text-decoration-none text-reset"><?php endif; ?>
        <div class="card stat-card h-100"><div class="card-body"><div class="stat-value"><?= number_format($chat_convos) ?></div><div class="stat-label">Chats (<?= (int) $chat_open ?> open)</div></div></div>
        <?php if ($chatOn): ?></a><?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header">Top Pages</div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>
                <?php foreach ($top_pages as $path => $n): ?>
                    <tr><td class="text-truncate" style="max-width:280px"><?= e($path) ?></td><td class="text-end fw-semibold"><?= number_format($n) ?></td></tr>
                <?php endforeach; ?>
                <?php if (! $top_pages): ?>
                    <tr><td class="text-center text-muted py-4">
                        No visits recorded yet.
                        <div class="small mt-1">Every page view on your public site is counted here — first-party, no third-party trackers.</div>
                    </td></tr>
                <?php endif; ?>
            </tbody></table></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header">Top Referrers</div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>
                <?php foreach ($top_referrers as $host => $n): ?>
                    <tr><td><?= e($host) ?></td><td class="text-end fw-semibold"><?= number_format($n) ?></td></tr>
                <?php endforeach; ?>
                <?php if (! $top_referrers): ?>
                    <tr><td class="text-center text-muted py-4">
                        No referrers yet.
                        <div class="small mt-1">This shows which sites send you traffic. Building <a href="<?= route('admin.backlinks.index') ?>">backlinks</a> is how you get entries here.</div>
                    </td></tr>
                <?php endif; ?>
            </tbody></table></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card"><div class="card-header">Recent Visits</div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>When</th><th>Page</th><th>Referrer</th></tr></thead>
                <tbody>
                    <?php foreach ($recent as $v): ?>
                        <tr>
                            <td class="text-nowrap text-muted small"><?= e($v['created_at'] ? date('d M, g:ia', strtotime($v['created_at'])) : '') ?></td>
                            <td class="text-truncate" style="max-width:260px"><?= e($v['path']) ?></td>
                            <td class="text-truncate text-muted small" style="max-width:260px"><?= e($v['referrer'] ?: 'Direct') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (! $recent): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">
                            No visits yet.
                            <div class="small mt-1">Nobody has landed on the public site since tracking started. Publish an <a href="<?= route('admin.blogs.index') ?>">article</a> or work through your <a href="<?= route('admin.backlinks.index') ?>">backlink list</a> to bring people in.</div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">First-party analytics — no third-party trackers. See <a href="<?= route('admin.chat.index') ?>">Live Chat</a> for conversation detail.</p>
<?php $this->endSection(); ?>
