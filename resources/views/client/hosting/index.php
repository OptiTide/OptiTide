<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<p class="text-muted mb-4">Your hosting accounts. Open cPanel to manage email, files, databases and more.</p>

<?php if (! empty($apps)): ?>
    <h2 class="h6 fw-bold mb-2">Your Apps</h2>
    <div class="row g-3 mb-4">
        <?php foreach ($apps as $app): ?>
            <?php
            $engagement = $app_engagements[$app['engagement_id']] ?? null;
            // Empty whenever nothing bills this app — we show no price at all
            // rather than a misleading "$0.00".
            $price = \App\Models\ClientApp::priceLabel($app, $engagement);
            ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-bold"><?= e($app['name']) ?><?php if ($app['environment']): ?> <span class="badge badge-soft"><?= e($app['environment']) ?></span><?php endif; ?></div>
                            <div class="text-muted small"><?= e($app['url']) ?></div>
                            <?php if ($price !== ''): ?>
                                <div class="small mt-1">
                                    <span class="money fw-semibold"><?= e($price) ?></span>
                                    <?php if ($engagement): ?>
                                        <span class="text-muted">as part of</span>
                                        <a href="<?= route('portal.services') ?>#engagement-<?= (int) $engagement['id'] ?>"><?= e($engagement['label']) ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="<?= e($app['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-brand"><i class="bi bi-box-arrow-up-right"></i> Open</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <h2 class="h6 fw-bold mb-2">Hosting</h2>
<?php endif; ?>

<?php if (! $accounts): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-hdd-network fs-3 d-block mb-2"></i>
        <div class="fw-semibold text-body">No Hosting Yet</div>
        <p class="mb-3">Hosting is where your website actually lives. Take it on with us and this page becomes your control panel — email accounts, files, backups and how much space you're using.</p>
        <a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand"><i class="bi bi-bag-plus"></i> Browse Hosting Plans</a>
    </div></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($accounts as $a): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold h6 mb-0"><?= e($a['domain']) ?></div>
                            <div class="text-muted small"><?= e($a['plan'] ?: 'Hosting') ?></div>
                        </div>
                        <span class="badge <?= $a['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($a['status'])) ?></span>
                    </div>
                    <?php if ($a['disk_used_mb'] !== null): ?>
                        <?php $pct = $a['disk_limit_mb'] ? min(100, round($a['disk_used_mb'] / max(1, $a['disk_limit_mb']) * 100)) : null; ?>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Disk Usage</span>
                                <span><?= number_format((int) $a['disk_used_mb']) ?><?= $a['disk_limit_mb'] !== null ? ' / ' . number_format((int) $a['disk_limit_mb']) : '' ?> MB</span>
                            </div>
                            <?php if ($pct !== null): ?>
                                <div class="progress" style="height:6px"><div class="progress-bar bg-brand" style="width:<?= $pct ?>%"></div></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= route('portal.hosting.login', ['id' => $a['id']]) ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-brand w-100"<?= $connected ? '' : ' disabled title="Contact support to access cPanel"' ?>><i class="bi bi-box-arrow-up-right"></i> Open cPanel</button>
                    </form>
                    <?php if (! $connected): ?><div class="text-muted small mt-2 text-center">Need cPanel access? <a href="<?= route('portal.support.create') ?>">Contact support</a>.</div><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php $this->endSection(); ?>
