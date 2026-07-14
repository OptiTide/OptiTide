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

<x-store-layout
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

    <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <a href="{{ route('blog.index') }}" class="text-sm font-medium text-sky-600 transition hover:text-sky-700">← All articles</a>

        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-900">{{ $blog->title }}</h1>
        <p class="mt-4 text-sm font-medium text-slate-400">
            Published {{ $blog->published_at?->format('j M Y') }}
            @if ($blog->author) · {{ $blog->author->name }} @endif
        </p>

        <div class="prose prose-slate mt-10 max-w-none prose-headings:font-semibold prose-a:text-sky-600">
            {!! $blog->safeBody() !!}
        </div>
    </article>
</x-store-layout>
