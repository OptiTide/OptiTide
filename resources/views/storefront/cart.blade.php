<x-store-layout title="Your Cart — OptiTide">
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Your cart</h1>

        @if ($lines->isEmpty())
            <div class="mt-10 rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <p class="text-slate-600">Your cart is empty.</p>
                <a href="{{ route('services.index') }}" class="mt-4 inline-block rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                    Browse services
                </a>
            </div>
        @else
            <ul class="mt-8 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($lines as $line)
                    <li class="flex items-center justify-between gap-4 py-5">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $line['product']->name }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ $line['product']->price->format() }} each
                                @if ($line['quantity'] > 1) &times; {{ $line['quantity'] }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-5">
                            <p class="font-semibold text-slate-900">{{ $line['total']->format() }}</p>
                            <form method="POST" action="{{ route('cart.remove', $line['product']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-slate-400 transition hover:text-rose-600" aria-label="Remove {{ $line['product']->name }}">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 flex items-center justify-between">
                <p class="text-sm text-slate-500">Subtotal <span class="text-xs">(AUD, GST handled at invoice)</span></p>
                <p class="text-2xl font-bold tracking-tight text-slate-900">{{ $subtotal->format() }}</p>
            </div>

            <form method="POST" action="{{ route('checkout.store') }}" class="mt-8">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-sky-500">
                    Proceed to secure checkout
                </button>
            </form>
            @guest
                <p class="mt-3 text-center text-xs text-slate-500">You'll be asked to sign in or create an account before payment.</p>
            @endguest

            <p class="mt-6 text-center text-xs text-slate-400">Payments are processed securely by Stripe. We never see your card details.</p>
        @endif
    </section>
</x-store-layout>
