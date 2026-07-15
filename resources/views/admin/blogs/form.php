<?php
$this->extends('layouts.admin');
$isEdit = $post !== null;
$action = $isEdit ? route('admin.blogs.update', ['id' => $post['id']]) : route('admin.blogs.store');
$pubDate = $post['published_at'] ?? '';
$pubDate = $pubDate ? date('Y-m-d', strtotime($pubDate)) : '';
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="<?= route('admin.blogs.index') ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-arrow-left"></i> All articles</a>
        <div class="d-flex gap-2">
            <?php if ($isEdit && $post['status'] === 'published'): ?>
                <a href="<?= route('blog.show', ['slug' => $post['slug']]) ?>" target="_blank" class="btn btn-sm btn-outline-brand"><i class="bi bi-box-arrow-up-right"></i> View</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-sm btn-brand"><i class="bi bi-check-lg"></i> Save Article</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="<?= e(old('title', $post['title'] ?? '')) ?>" class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" required autofocus>
                        <?php if (error('title')): ?><div class="invalid-feedback"><?= e(error('title')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excerpt <span class="text-muted small">(shown on cards & in search results)</span></label>
                        <textarea name="excerpt" rows="2" class="form-control" maxlength="500"><?= e(old('excerpt', $post['excerpt'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Body <span class="text-muted small">(HTML — headings, paragraphs, lists, links, images)</span></label>
                        <textarea name="body" rows="20" class="form-control font-monospace" style="font-size:.85rem" required><?= e(old('body', $post['body'] ?? '')) ?></textarea>
                        <?php if (error('body')): ?><div class="text-danger small mt-1"><?= e(error('body')) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Publishing</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <?php $st = old('status', $post['status'] ?? 'draft'); ?>
                        <select name="status" class="form-select">
                            <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $st === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Publish date</label>
                        <input type="date" name="published_at" value="<?= e(old('published_at', $pubDate)) ?>" class="form-control">
                        <div class="form-text">Leave blank to publish now. A future date schedules it.</div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">Organise</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" value="<?= e(old('category', $post['category'] ?? '')) ?>" class="form-control" list="blog-cats" placeholder="e.g. SEO">
                        <datalist id="blog-cats"><option>Web Design</option><option>SEO</option><option>Social Media</option><option>Web Hosting</option><option>Small Business</option></datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" value="<?= e(old('author', $post['author'] ?? 'OptiTide')) ?>" class="form-control">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Slug <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="slug" value="<?= e(old('slug', $post['slug'] ?? '')) ?>" class="form-control" placeholder="auto from title">
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">SEO</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Cover image URL/path</label>
                        <input type="text" name="cover_image" value="<?= e(old('cover_image', $post['cover_image'] ?? '')) ?>" class="form-control" placeholder="/assets/img/blog/... or https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Focus keywords</label>
                        <input type="text" name="keywords" value="<?= e(old('keywords', $post['keywords'] ?? '')) ?>" class="form-control" placeholder="comma, separated">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta title</label>
                        <input type="text" name="meta_title" value="<?= e(old('meta_title', $post['meta_title'] ?? '')) ?>" class="form-control" maxlength="255" placeholder="Defaults to the title">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" rows="3" class="form-control" maxlength="320" placeholder="Defaults to the excerpt"><?= e(old('meta_description', $post['meta_description'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->endSection(); ?>
