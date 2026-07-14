{{-- @include('storefront._plan-card', ['product' => $p, 'popular' => bool]) --}}
<div class="col-md-6 col-lg-4">
    <div class="card h-100 border rounded-4 p-2 bg-white {{ ($popular ?? false) ? 'pricing-popular' : 'shadow-sm card-lift' }}">
        @if ($popular ?? false)<span class="ribbon"><i class="bi bi-star-fill me-1"></i>POPULAR</span>@endif
        <div class="card-body d-flex flex-column">
            <h3 class="h5 fw-bold mb-1">{{ $product->name }}</h3>
            <p class="text-secondary small">{{ $product->description }}</p>
            <div class="my-3">
                <span class="display-6 fw-bold">{{ $product->price->format() }}</span>
                @if ($product->isSubscription())<span class="text-secondary small">/{{ $product->billing_interval }}</span>@else<span class="text-secondary small">one-time</span>@endif
                <div class="text-secondary small">GST incl.</div>
            </div>
            <ul class="list-unstyled d-grid gap-2 mb-4 flex-grow-1 text-start">
                @foreach ($product->features ?? [] as $feature)
                    <li class="d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i><span>{{ $feature }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('services.show', $product) }}" class="btn {{ ($popular ?? false) ? 'btn-accent' : 'btn-outline-primary' }} btn-lg mt-auto">Choose {{ $product->name }}</a>
        </div>
    </div>
</div>
