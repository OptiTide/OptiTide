<x-site-layout :title="$product->name.' — OptiTide'">
    <section class="section">
        <div class="container">
            @if (request()->boolean('subscribed'))
                <div class="alert alert-success d-flex align-items-center border-0 shadow-sm rounded-4 mb-4">
                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                    <span>Your subscription is being activated — you'll see it in your client portal shortly.</span>
                </div>
            @endif

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('services.index') }}" class="text-decoration-none">Services</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="row g-5 mt-1">
                <div class="col-lg-8">
                    <p class="eyebrow text-primary mb-2">{{ $product->category->getLabel() }}</p>
                    <h1 class="fw-bold display-5 text-dark">{{ $product->name }}</h1>
                    <p class="fs-5 text-secondary mt-3" style="max-width:42rem">{{ $product->description }}</p>

                    @if ($product->features)
                        <h2 class="fw-bold fs-4 text-dark mt-5">What's included</h2>
                        <ul class="list-unstyled row g-3 mt-1" style="max-width:42rem">
                            @foreach ($product->features as $feature)
                                <li class="col-sm-6 d-flex text-secondary">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($product->category === \App\Enums\ProductCategory::WebDevelopment)
                        <div class="card border-0 bg-light rounded-4 mt-5" style="max-width:42rem">
                            <div class="card-body p-4 text-secondary">
                                <p class="fw-bold text-dark mb-2"><i class="bi bi-compass text-primary me-2"></i>How it works</p>
                                <p class="mb-0">After checkout you'll complete a short onboarding form — your brand, your colors, your goals. Our design team then crafts your site and presents it in your client portal for review before anything goes live.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 sticky-lg-top" style="top:6rem">
                        <div class="card-body p-4">
                            <p class="fw-bold display-6 text-dark mb-1">
                                {{ $product->price->format() }}@if ($product->isSubscription())<span class="fs-6 fw-medium text-secondary">/{{ $product->billing_interval }}</span>@endif
                            </p>
                            <p class="small text-secondary">AUD @if ($product->isSubscription()) &middot; billed {{ $product->billing_interval }}ly, cancel anytime @else &middot; one-time @endif</p>

                            @if ($product->isSubscription())
                                <form method="POST" action="{{ route('subscribe.store', $product) }}" class="mt-4 d-grid">
                                    @csrf
                                    <button type="submit" class="btn btn-accent btn-lg fw-semibold">
                                        <i class="bi bi-arrow-repeat me-1"></i>Subscribe now
                                    </button>
                                </form>
                                @guest
                                    <p class="mt-3 text-center small text-secondary">You'll be asked to sign in or create an account first.</p>
                                @endguest
                            @else
                                <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-4 d-grid">
                                    @csrf
                                    <button type="submit" class="btn btn-accent btn-lg fw-semibold">
                                        <i class="bi bi-cart3 me-1"></i>Add to cart
                                    </button>
                                </form>
                            @endif

                            <p class="mt-4 pt-3 border-top small text-secondary mb-0">
                                Questions first? <a href="{{ route('contact.show') }}" class="fw-semibold text-decoration-none">Talk to us</a> — we'll help you pick the right fit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-site-layout>
