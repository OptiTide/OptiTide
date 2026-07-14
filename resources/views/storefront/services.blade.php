<x-site-layout
    title="Services &amp; Pricing — Web Design, SEO, Social Media &amp; Hosting | OptiTide"
    description="Web design, SEO, social media &amp; hosting plans for Australian businesses — transparent AUD pricing (GST incl.), no lock-in. Websites from $750, hosting from $25/mo."
>
    <section class="section">
        <div class="container">
            <div class="text-center mx-auto" style="max-width:44rem">
                <p class="eyebrow text-primary mb-2">Services &amp; pricing</p>
                <h1 class="fw-bold display-5 text-dark">Transparent AUD pricing</h1>
                <p class="fs-5 text-secondary mt-3 mb-0">One-time packages go in your cart; hosting plans are monthly subscriptions you activate directly.</p>
            </div>

            @foreach (\App\Enums\ProductCategory::cases() as $category)
                @continue(! isset($grouped[$category->value]))
                <div class="mt-5 pt-4" id="{{ $category->value === 'web_development' ? 'web' : ($category->value === 'hosting' ? 'hosting' : $category->value) }}">
                    <h2 class="fw-bold display-6 text-dark text-center mb-4">{{ $category->getLabel() }}</h2>

                    @php
                        // Bootstrap column widths per category (mirrors the old grid density).
                        $col = match ($category) {
                            \App\Enums\ProductCategory::Seo => 'col-md-6 col-lg-3',
                            \App\Enums\ProductCategory::WebDevelopment => 'col-md-6 col-lg-4',
                            default => 'col-md-6',
                        };
                    @endphp
                    <div class="row g-4 justify-content-center">
                        @foreach ($grouped[$category->value] as $product)
                            <div class="{{ $col }}">
                                <div class="card border-0 shadow-sm rounded-4 card-lift h-100">
                                    <div class="card-body d-flex flex-column p-4">
                                        <h3 class="h5 fw-bold text-dark mb-1">{{ $product->name }}</h3>
                                        <p class="text-secondary small mb-0">{{ $product->description }}</p>
                                        <p class="fw-bold text-dark mt-4 mb-0" style="font-size:2rem;line-height:1.1">
                                            {{ $product->price->format() }}@if ($product->isSubscription())<span class="fs-6 fw-medium text-secondary">/{{ $product->billing_interval }}</span>@endif
                                        </p>
                                        <ul class="list-unstyled d-grid gap-2 small text-secondary mt-4 mb-0 flex-grow-1">
                                            @foreach (array_slice($product->features ?? [], 0, 4) as $feature)
                                                <li class="d-flex">
                                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                                    <span>{{ $feature }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="d-flex gap-2 mt-4">
                                            @if ($product->isSubscription())
                                                <a href="{{ route('services.show', $product) }}" class="btn btn-primary fw-semibold flex-fill">View plan</a>
                                            @else
                                                <form method="POST" action="{{ route('cart.add', $product) }}" class="flex-fill">
                                                    @csrf
                                                    <button type="submit" class="btn btn-accent fw-semibold w-100"><i class="bi bi-cart3 me-1"></i>Add to cart</button>
                                                </form>
                                                <a href="{{ route('services.show', $product) }}" class="btn btn-outline-primary fw-semibold">Details</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-site-layout>
