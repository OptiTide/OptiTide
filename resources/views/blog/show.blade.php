@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $blog->title,
        'description' => $blog->metaDescription(),
        'datePublished' => $blog->published_at?->toIso8601String(),
        'dateModified' => $blog->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => 'OptiTide'],
        'publisher' => ['@type' => 'Organization', 'name' => 'OptiTide'],
        'mainEntityOfPage' => route('blog.show', $blog),
    ];
    if ($blog->ogImage()) {
        $schema['image'] = $blog->ogImage();
    }
@endphp

<x-site-layout
    :title="$blog->metaTitle()"
    :description="$blog->metaDescription()"
    :canonical="route('blog.show', $blog)"
    :ogImage="$blog->ogImage()"
    ogType="article"
>
    <x-slot:head>
        {{-- JSON_HEX_TAG stops a title/description containing </script> from breaking out of this block. --}}
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    </x-slot:head>

    <article class="section">
        <div class="container">
            <div class="mx-auto" style="max-width:46rem">
                <a href="{{ route('blog.index') }}" class="d-inline-flex align-items-center text-decoration-none fw-medium text-primary mb-4">
                    <i class="bi bi-arrow-left me-2"></i>All articles
                </a>

                <p class="eyebrow text-primary mb-2"><i class="bi bi-journal-text me-1"></i>Article</p>
                <h1 class="fw-bold display-5 lh-sm text-dark mb-3">{{ $blog->title }}</h1>
                <p class="d-flex flex-wrap align-items-center gap-2 text-secondary small mb-0">
                    <span><i class="bi bi-calendar3 me-1"></i>Published {{ $blog->published_at?->format('j M Y') }}</span>
                    @if ($blog->author)
                        <span class="text-muted">·</span>
                        <span><i class="bi bi-person-circle me-1"></i>{{ $blog->author->name }}</span>
                    @endif
                </p>

                <hr class="my-4">

                <div class="blog-prose fs-5 lh-lg text-body">
                    {!! $blog->safeBody() !!}
                </div>
            </div>
        </div>
    </article>
</x-site-layout>
