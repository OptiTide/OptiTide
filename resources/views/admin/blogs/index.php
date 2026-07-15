<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted"><?= count($posts) ?> article<?= count($posts) === 1 ? '' : 's' ?></div>
    <div class="d-flex gap-2">
        <a href="<?= route('blog.index') ?>" target="_blank" class="btn btn-sm btn-outline-brand"><i class="bi bi-box-arrow-up-right"></i> View Blog</a>
        <a href="<?= route('admin.blogs.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Article</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th class="text-end">Views</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($p['title']) ?><div class="text-muted small">/blog/<?= e($p['slug']) ?></div></td>
                        <td><?= $p['category'] ? '<span class="badge badge-soft">' . e($p['category']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if ($p['status'] === 'published'): ?>
                                <span class="badge text-bg-success">Published</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= e($p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '—') ?></td>
                        <td class="text-end"><?= (int) ($p['views'] ?? 0) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($p['status'] === 'published'): ?>
                                <a href="<?= route('blog.show', ['slug' => $p['slug']]) ?>" target="_blank" class="btn btn-sm btn-link" title="View"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                            <a href="<?= route('admin.blogs.edit', ['id' => $p['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= route('admin.blogs.destroy', ['id' => $p['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this article?')">
                                <?= csrf_field() ?><?= method_field('DELETE') ?>
                                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($posts === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No articles yet. <a href="<?= route('admin.blogs.create') ?>">Write your first post.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
