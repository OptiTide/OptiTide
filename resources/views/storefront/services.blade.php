<x-store-layout title="Services & Pricing — OptiTide">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900">Services &amp; pricing</h1>
            <p class="mt-3 text-slate-600">Transparent AUD pricing. One-time packages go in your cart; hosting plans are monthly subscriptions you activate directly.</p>
        </div>

        @foreach (\App\Enums\ProductCategory::cases() as $category)
            @continue(! isset($grouped[$category->value]))
            <div class="mt-16" id="{{ $category->value === 'web_development' ? 'web' : ($category->value === 'hosting' ? 'hosting' : $category->value) }}">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $category->getLabel() }}</h2>

                @php
                    // Full literal class names so Tailwind's scanner sees them.
                    $grid = match ($category) {
                        \App\Enums\ProductCategory::Seo => 'lg:grid-cols-4',
                        \App\Enums\ProductCategory::WebDevelopment => 'lg:grid-cols-3',
                        default => 'lg:grid-cols-2',
                    };
                @endphp
                <div class="mt-8 grid gap-6 sm:grid-cols-2 {{ $grid }}">
                    @foreach ($grouped[$category->value] as $product)
                        <div class="flex flex-col rounded-2xl border border-slate-200 p-6 transition hover:border-sky-300 hover:shadow-md">
                            <h3 class="font-semibold text-slate-900">{{ $product->name }}</h3>
                            <p class="mt-1.5 line-clamp-2 flex-none text-sm text-slate-600">{{ $product->description }}</p>
                            <p class="mt-5 text-3xl font-bold tracking-tight text-slate-900">
                                {{ $product->price->format() }}@if ($product->isSubscription())<span class="text-sm font-medium text-slate-500">/{{ $product->billing_interval }}</span>@endif
                            </p>
                            <ul class="mt-4 flex-1 space-y-2 text-sm text-slate-600">
                                @foreach (array_slice($product->features ?? [], 0, 4) as $feature)
                                    <li class="flex gap-2">
                                        <svg class="h-5 w-5 flex-none text-sky-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-6 flex gap-2">
                                @if ($product->isSubscription())
                                    <a href="{{ route('services.show', $product) }}" class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-700">View plan</a>
                                @else
                                    <form method="POST" action="{{ route('cart.add', $product) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500">Add to cart</button>
                                    </form>
                                    <a href="{{ route('services.show', $product) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400">Details</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</x-store-layout>
