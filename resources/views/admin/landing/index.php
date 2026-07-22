<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
        <p class="text-muted small mb-0" style="max-width:640px">
            Pages built to rank for one search phrase each — <strong>/web-design-perth</strong> rather than a
            buried sub-page. Give each one a real target keyword, genuinely useful content and a few FAQs
            (those can win the "People also ask" box without outranking anyone).
            <br><br>
            <strong>One warning worth heeding:</strong> a pile of near-identical city pages is what Google calls
            <em>doorway pages</em>, and it can pull down the whole domain rather than just failing to rank.
            Every page here should say something the others don't.
        </p>
        <a href="<?= route('admin.landing.create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New Page</a>
    </div>
</div>

<div class="card">
    <div class="card-header">Landing Pages <span class="text-muted">(<?= count($pages) ?>)</span></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Page</th><th>Target keyword</th><th>Status</th><th class="text-end">Views</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($p['title']) ?></div>
                            <div class="small text-muted font-monospace">/<?= e($p['slug']) ?></div>
                        </td>
                        <td class="small"><?= e($p['keyword'] ?: '—') ?><?php if (! empty($p['location'])): ?><div class="text-muted"><?= e($p['location']) ?></div><?php endif; ?></td>
                        <td>
                            <span class="badge <?= $p['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e(ucfirst($p['status'])) ?></span>
                        </td>
                        <td class="text-end small text-muted"><?= number_format((int) $p['views']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($p['status'] === 'published'): ?>
                                <a href="/<?= e($p['slug']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link" title="View live"><i class="bi bi-box-arrow-up-right"></i></a>
                            <?php endif; ?>
                            <a href="<?= route('admin.landing.edit', ['id' => $p['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= route('admin.landing.destroy', ['id' => $p['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this page? Any ranking it has built will be lost.')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($pages === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-signpost-split fs-3 d-block mb-2 opacity-50"></i>
                        No landing pages yet — create one for a phrase your customers actually search.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
