<x-store-layout :title="$product->name.' — OptiTide'">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        @if (request()->boolean('subscribed'))
            <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                Your subscription is being activated — you'll see it in your client portal shortly.
            </div>
        @endif

        <nav class="text-sm text-slate-500">
            <a href="{{ route('services.index') }}" class="hover:text-slate-800">Services</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800">{{ $product->name }}</span>
        </nav>

        <div class="mt-8 grid gap-12 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <span class="inline-block rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-sky-700">
                    {{ $product->category->getLabel() }}
                </span>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $product->name }}</h1>
                <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ $product->description }}</p>

                @if ($product->features)
                    <h2 class="mt-10 text-lg font-semibold text-slate-900">What's included</h2>
                    <ul class="mt-4 grid max-w-2xl gap-3 sm:grid-cols-2">
                        @foreach ($product->features as $feature)
                            <li class="flex gap-2.5 text-sm text-slate-600">
                                <svg class="h-5 w-5 flex-none text-sky-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($product->category === \App\Enums\ProductCategory::WebDevelopment)
                    <div class="mt-10 max-w-2xl rounded-2xl bg-slate-50 p-6 text-sm leading-6 text-slate-600">
                        <p class="font-semibold text-slate-900">How it works</p>
                        <p class="mt-2">After checkout you'll complete a short onboarding form — your brand, your colors, your goals. Our design team then crafts your site and presents it in your client portal for review before anything goes live.</p>
                    </div>
                @endif
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <p class="text-4xl font-bold tracking-tight text-slate-900">
                        {{ $product->price->format() }}@if ($product->isSubscription())<span class="text-base font-medium text-slate-500">/{{ $product->billing_interval }}</span>@endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500">AUD @if ($product->isSubscription()) &middot; billed {{ $product->billing_interval }}ly, cancel anytime @else &middot; one-time @endif</p>

                    @if ($product->isSubscription())
                        <form method="POST" action="{{ route('subscribe.store', $product) }}" class="mt-6">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
                                Subscribe now
                            </button>
                        </form>
                        @guest
                            <p class="mt-3 text-center text-xs text-slate-500">You'll be asked to sign in or create an account first.</p>
                        @endguest
                    @else
                        <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-6">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
                                Add to cart
                            </button>
                        </form>
                    @endif

                    <p class="mt-6 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">
                        Questions first? <a href="{{ route('contact.show') }}" class="font-semibold text-sky-700 hover:underline">Talk to us</a> — we'll help you pick the right fit.
                    </p>
                </div>
            </aside>
        </div>
    </section>
</x-store-layout>
