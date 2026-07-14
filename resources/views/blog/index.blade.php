<x-store-layout
    title="Blog — OptiTide"
    description="Practical guidance on web design, SEO, social media, and hosting from the OptiTide team."
    :canonical="url()->full()"
>
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-sky-600">Insights</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-900">The OptiTide Blog</h1>
            <p class="mt-3 text-lg leading-7 text-slate-600">
                Getting found online, converting visitors, and keeping your site fast — the practical way.
            </p>
        </div>

        @if ($posts->isEmpty())
            <p class="mt-12 text-slate-500">No articles published yet — check back soon.</p>
        @else
            <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <article class="flex flex-col rounded-2xl border border-slate-200 p-6 transition hover:border-sky-200 hover:shadow-lg">
                        <h2 class="text-xl font-semibold leading-7 text-slate-900">
                            <a href="{{ route('blog.show', $post) }}" class="transition hover:text-sky-600">{{ $post->title }}</a>
                        </h2>
                        <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">{{ $post->metaDescription() }}</p>
                        <p class="mt-5 text-xs font-medium text-slate-400">
                            {{ $post->published_at?->format('j M Y') }}
                            @if ($post->author) · {{ $post->author->name }} @endif
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        @endif
    </section>
</x-store-layout>
