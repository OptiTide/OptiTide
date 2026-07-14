<x-site-layout title="Your Cart — OptiTide">
    <section class="section">
        <div class="container" style="max-width:48rem">
            <p class="eyebrow text-primary">Checkout</p>
            <h1 class="fw-bold display-6 text-dark mb-0">Your cart</h1>

            @if ($lines->isEmpty())
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-body text-center py-5">
                        <div class="feature-ico mx-auto mb-3">
                            <i class="bi bi-cart3 text-primary"></i>
                        </div>
                        <p class="text-secondary mb-4">Your cart is empty.</p>
                        <a href="{{ route('services.index') }}" class="btn btn-primary fw-semibold">
                            <i class="bi bi-grid me-1"></i>Browse services
                        </a>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <ul class="list-group list-group-flush">
                        @foreach ($lines as $line)
                            <li class="list-group-item d-flex align-items-center justify-content-between gap-3 py-3 px-4">
                                <div>
                                    <p class="fw-semibold text-dark mb-1">{{ $line['product']->name }}</p>
                                    <p class="small text-secondary mb-0">
                                        {{ $line['product']->price->format() }} each
                                        @if ($line['quantity'] > 1) &times; {{ $line['quantity'] }} @endif
                                    </p>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <p class="fw-semibold text-dark mb-0">{{ $line['total']->format() }}</p>
                                    <form method="POST" action="{{ route('cart.remove', $line['product']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $line['product']->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4">
                    <p class="small text-secondary mb-0">Subtotal <span class="text-muted">(AUD, GST handled at invoice)</span></p>
                    <p class="fs-3 fw-bold text-dark mb-0">{{ $subtotal->format() }}</p>
                </div>

                <form method="POST" action="{{ route('checkout.store') }}" class="mt-4 d-grid">
                    @csrf
                    <button type="submit" class="btn btn-accent btn-lg fw-semibold">
                        <i class="bi bi-lock-fill me-2"></i>Proceed to secure checkout
                    </button>
                </form>
                @guest
                    <p class="text-center small text-secondary mt-3 mb-0">You'll be asked to sign in or create an account before payment.</p>
                @endguest

                <p class="text-center small text-muted mt-4 mb-0">
                    <i class="bi bi-shield-lock me-1"></i>Payments are processed securely by Stripe. We never see your card details.
                </p>
            @endif
        </div>
    </section>
</x-site-layout>
