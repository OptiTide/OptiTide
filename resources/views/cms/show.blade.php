<x-store-layout
    :title="$page->metaTitle()"
    :description="$page->metaDescription()"
    :canonical="route('cms.show', $page->slug)"
    ogType="article"
>
    <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900">{{ $page->title }}</h1>

        @if ($page->excerpt)
            <p class="mt-4 text-lg leading-7 text-slate-600">{{ $page->excerpt }}</p>
        @endif

        <div class="prose prose-slate mt-8 max-w-none prose-headings:font-semibold prose-a:text-sky-600">
            {!! $page->safeBody() !!}
        </div>

        <p class="mt-10 text-xs text-slate-400">Last updated {{ $page->updated_at?->format('j F Y') }}</p>
    </article>
</x-store-layout>
