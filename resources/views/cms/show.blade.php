<x-site-layout
    :title="$page->metaTitle()"
    :description="$page->metaDescription()"
    :canonical="route('cms.show', $page->slug)"
    ogType="article"
>
    <section class="section">
        <div class="container">
            <article class="mx-auto" style="max-width:48rem">
                <h1 class="fw-bold display-5 text-dark lh-sm">{{ $page->title }}</h1>

                @if ($page->excerpt)
                    <p class="fs-5 text-secondary mt-3">{{ $page->excerpt }}</p>
                @endif

                <div class="mt-4">
                    {!! $page->safeBody() !!}
                </div>

                <p class="small text-secondary mt-5 mb-0">Last updated {{ $page->updated_at?->format('j F Y') }}</p>
            </article>
        </div>
    </section>
</x-site-layout>
