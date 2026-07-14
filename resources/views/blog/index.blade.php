<x-site-layout
    title="Blog — OptiTide"
    description="Practical guidance on web design, SEO, social media, and hosting from the OptiTide team."
    :canonical="url()->full()"
>
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mx-auto" style="max-width:42rem">
                <p class="eyebrow text-primary mb-2">Insights</p>
                <h1 class="fw-bold display-5 text-dark">The OptiTide Blog</h1>
                <p class="fs-5 text-secondary mb-0">
                    Getting found online, converting visitors, and keeping your site fast — the practical way.
                </p>
            </div>

            @if ($posts->isEmpty())
                <div class="text-center text-secondary mt-5">
                    <i class="bi bi-journal-text fs-1 text-primary d-block mb-3"></i>
                    <p class="mb-0">No articles published yet — check back soon.</p>
                </div>
            @else
                <div class="row g-4 mt-4">
                    @foreach ($posts as $post)
                        <div class="col-md-6 col-lg-4 d-flex">
                            <article class="card border-0 shadow-sm rounded-4 card-lift w-100">
                                <div class="card-body d-flex flex-column p-4">
                                    <h2 class="h5 fw-semibold mb-3">
                                        <a href="{{ route('blog.show', $post) }}" class="text-dark text-decoration-none stretched-link">{{ $post->title }}</a>
                                    </h2>
                                    <p class="text-secondary small flex-grow-1 mb-3">{{ $post->metaDescription() }}</p>
                                    <p class="text-body-tertiary small fw-medium mb-0">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $post->published_at?->format('j M Y') }}
                                        @if ($post->author) · <i class="bi bi-person me-1"></i>{{ $post->author->name }} @endif
                                    </p>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $posts->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>
</x-site-layout>
