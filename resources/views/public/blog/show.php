<?php
$this->extends('layouts.marketing');
$appUrl = rtrim(config('app.url'), '/');
$coverRaw = trim((string) ($post['cover_image'] ?? ''));
$cover = $coverRaw === '' ? '' : (str_starts_with($coverRaw, 'http') ? $coverRaw : $appUrl . '/' . ltrim($coverRaw, '/'));
$coverFn = function (array $p) use ($appUrl): string {
    $c = trim((string) ($p['cover_image'] ?? ''));
    if ($c === '') {
        return '';
    }

    return str_starts_with($c, 'http') ? $c : $appUrl . '/' . ltrim($c, '/');
};
?>
<?php $this->section('content'); ?>

<article class="mk-section mk-blog-article">
    <div class="mk-container mk-blog-narrow">
        <nav class="mk-blog-crumb mb-3" aria-label="Breadcrumb">
            <a href="/">Home</a> <span>/</span> <a href="<?= route('blog.index') ?>">Blog</a> <span>/</span> <span class="text-muted"><?= e($post['title']) ?></span>
        </nav>

        <?php if (! empty($post['category'])): ?><span class="mk-blog-cat mb-2"><?= e($post['category']) ?></span><?php endif; ?>
        <h1 class="mk-blog-title"><?= e($post['title']) ?></h1>
        <div class="mk-blog-meta mb-4">
            By <?= e($post['author'] ?: 'OptiTide') ?>
            &middot; <?= e($post['published_at'] ? date('d M Y', strtotime($post['published_at'])) : '') ?>
            &middot; <?= \App\Models\Blog::readingMinutes($post['body']) ?> min read
        </div>

        <?php if ($cover !== ''): ?>
            <img src="<?= e($cover) ?>" alt="<?= e($post['title']) ?>" class="mk-blog-hero-img mb-4">
        <?php endif; ?>

        <div class="mk-blog-body">
            <?= $post['body'] ?>
        </div>

        <div class="mk-blog-cta mt-5">
            <div>
                <div class="fw-bold h5 mb-1">Want results like these for your business?</div>
                <div class="text-muted">Get a free, no-obligation quote — web design, SEO, social media and hosting, all in one place.</div>
            </div>
            <a href="/#contact" class="btn btn-brand">Get a Free Quote</a>
        </div>

        <?php if ($related): ?>
            <div class="mt-5">
                <h2 class="h5 fw-bold mb-3">Keep reading</h2>
                <div class="row g-3">
                    <?php foreach ($related as $p): ?>
                        <?php $rimg = $coverFn($p); ?>
                        <div class="col-md-4">
                            <a href="<?= route('blog.show', ['slug' => $p['slug']]) ?>" class="mk-blog-card">
                                <div class="mk-blog-card-img mk-blog-card-img--sm"<?= $rimg !== '' ? ' style="background-image:url(\'' . e($rimg) . '\')"' : '' ?>>
                                    <?php if ($rimg === ''): ?><i class="bi bi-newspaper"></i><?php endif; ?>
                                </div>
                                <div class="mk-blog-card-body">
                                    <h3 class="fs-6"><?= e($p['title']) ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>
<?php $this->endSection(); ?>
