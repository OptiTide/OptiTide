<x-store-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-slate-950">
        <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-sky-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 right-0 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8">
            <div class="max-w-2xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-sky-500/30 bg-sky-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-sky-300">
                    Australian-owned &middot; serving clients worldwide
                </p>
                <h1 class="text-4xl font-bold tracking-tight text-white sm:text-6xl">
                    Websites, SEO &amp; hosting that move your business forward
                </h1>
                <p class="mt-6 text-lg leading-8 text-slate-300">
                    OptiTide designs bespoke websites, grows your search visibility, and keeps everything online — so you can stay focused on running your business.
                </p>
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="{{ route('services.index') }}" class="rounded-xl bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">
                        Explore services
                    </a>
                    <a href="{{ route('contact.show') }}" class="rounded-xl border border-slate-600 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:text-white">
                        Talk to us
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Web design tiers --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Web design, three ways</h2>
            <p class="mt-3 text-slate-600">From a polished starter site to fully bespoke platform engineering — every build is designed around your brand, never a template.</p>
        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-3">
            @foreach ($webTiers as $tier)
                <div class="flex flex-col rounded-2xl border p-8 transition hover:shadow-lg {{ $loop->last ? 'border-sky-600 shadow-md ring-1 ring-sky-600' : 'border-slate-200' }}">
                    @if ($loop->last)
                        <p class="mb-3 -mt-2 text-xs font-bold uppercase tracking-wider text-sky-600">Fully bespoke</p>
                    @endif
                    <h3 class="text-lg font-semibold text-slate-900">{{ $tier->name }}</h3>
                    <p class="mt-2 flex-none text-sm text-slate-600">{{ $tier->description }}</p>
                    <p class="mt-6 text-4xl font-bold tracking-tight text-slate-900">{{ $tier->price->format() }}</p>
                    <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-600">
                        @foreach ($tier->features ?? [] as $feature)
                            <li class="flex gap-2.5">
                                <svg class="h-5 w-5 flex-none text-sky-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('services.show', $tier) }}" class="mt-8 rounded-xl px-4 py-2.5 text-center text-sm font-semibold transition {{ $loop->last ? 'bg-sky-600 text-white hover:bg-sky-500' : 'bg-slate-900 text-white hover:bg-slate-700' }}">
                        View package
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Hosting --}}
    <section class="bg-slate-50" id="hosting">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Hosting that stays out of your way</h2>
                <p class="mt-3 text-slate-600">Monthly plans with SSL, monitoring, and — on Managed — updates and daily backups handled for you.</p>
            </div>

            <div class="mt-12 grid gap-8 md:max-w-4xl md:grid-cols-2">
                @foreach ($hostingPlans as $plan)
                    <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-8">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $plan->description }}</p>
                        <p class="mt-6 text-4xl font-bold tracking-tight text-slate-900">
                            {{ $plan->price->format() }}<span class="text-base font-medium text-slate-500">/month</span>
                        </p>
                        <ul class="mt-6 flex-1 space-y-2.5 text-sm text-slate-600">
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="flex gap-2.5">
                                    <svg class="h-5 w-5 flex-none text-sky-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('services.show', $plan) }}" class="mt-8 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-700">
                            View plan
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-slate-950 px-8 py-14 text-center sm:px-14">
            <h2 class="text-3xl font-bold tracking-tight text-white">Not sure where to start?</h2>
            <p class="mx-auto mt-3 max-w-xl text-slate-300">Tell us about your business and we'll recommend the right mix of design, SEO, and hosting — no obligation.</p>
            <a href="{{ route('contact.show') }}" class="mt-8 inline-block rounded-xl bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400">
                Get a free recommendation
            </a>
        </div>
    </section>
</x-store-layout>
