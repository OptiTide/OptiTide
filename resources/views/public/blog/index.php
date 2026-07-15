<?php
$this->extends('layouts.marketing');
$appUrl = rtrim(config('app.url'), '/');
$cover = function (array $p) use ($appUrl): string {
    $c = trim((string) ($p['cover_image'] ?? ''));
    if ($c === '') {
        return '';
    }

    return str_starts_with($c, 'http') ? $c : $appUrl . '/' . ltrim($c, '/');
};
?>
<?php $this->section('content'); ?>

<section class="mk-section mk-blog-index">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">OptiTide Blog</span>
            <h1 class="mk-h2">Insights to Grow Your Business Online</h1>
            <p class="mk-lead mx-auto">Practical, no-jargon advice on web design, SEO, social media and getting found on Google — written for Australian small businesses.</p>
        </div>

        <?php if ($categories): ?>
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-5">
                <a href="<?= route('blog.index') ?>" class="btn btn-sm <?= $activeCategory === '' ? 'btn-brand' : 'btn-outline-brand' ?>">All</a>
                <?php foreach ($categories as $c): ?>
                    <a href="<?= route('blog.index') ?>?category=<?= rawurlencode($c) ?>" class="btn btn-sm <?= strcasecmp($activeCategory, $c) === 0 ? 'btn-brand' : 'btn-outline-brand' ?>"><?= e($c) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (! $posts): ?>
            <div class="text-center text-muted py-5">No articles published yet — check back soon.</div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($posts as $p): ?>
                <?php $img = $cover($p); ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= route('blog.show', ['slug' => $p['slug']]) ?>" class="mk-blog-card">
                        <div class="mk-blog-card-img"<?= $img !== '' ? ' style="background-image:url(\'' . e($img) . '\')"' : '' ?>>
                            <?php if ($img === ''): ?><i class="bi bi-newspaper"></i><?php endif; ?>
                        </div>
                        <div class="mk-blog-card-body">
                            <?php if (! empty($p['category'])): ?><span class="mk-blog-cat"><?= e($p['category']) ?></span><?php endif; ?>
                            <h3><?= e($p['title']) ?></h3>
                            <p><?= e($p['excerpt']) ?></p>
                            <div class="mk-blog-meta">
                                <?= e($p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '') ?>
                                &middot; <?= \App\Models\Blog::readingMinutes($p['body']) ?> min read
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
