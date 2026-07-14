@props([
    'title' => 'OptiTide — Web Design, SEO & Hosting',
    'description' => 'An Australian digital agency building websites, search visibility, and reliable hosting for businesses worldwide.',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'head' => null,
])
<!DOCTYPE html>
<html lang="en-AU" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Australia geotargeting signals (complements the Search Console country setting) --}}
    <link rel="alternate" hreflang="en-au" href="{{ $canonical ?? url()->current() }}">
    <meta name="geo.region" content="AU">
    <meta name="geo.placename" content="Australia">

    {{-- Open Graph / Twitter cards --}}
    <meta property="og:site_name" content="OptiTide">
    <meta property="og:locale" content="en_AU">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <x-analytics />

    {{-- Site-wide Organization schema: AU address + areaServed + ABN. JSON_HEX_TAG
         so a stray </script> in a value can't break out of the block. --}}
    @php
        $company = config('company');
        $org = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $company['legal_name'] ?? 'OptiTide',
            'url' => rtrim(config('app.url'), '/'),
            'email' => $company['email'] ?? null,
            'areaServed' => 'AU',
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $company['address']['line1'] ?? null,
                'addressLocality' => $company['address']['locality'] ?? null,
                'addressRegion' => $company['address']['region'] ?? null,
                'postalCode' => $company['address']['postcode'] ?? null,
                'addressCountry' => 'AU',
            ]),
        ]);
        if (filled($company['abn'] ?? null)) {
            $org['identifier'] = ['@type' => 'PropertyValue', 'propertyID' => 'ABN', 'value' => $company['abn']];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($org, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>

    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-slate-900">
                Opti<span class="text-sky-600">Tide</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('services.index') }}" class="transition hover:text-slate-900">Services</a>
                <a href="{{ route('services.index') }}#hosting" class="transition hover:text-slate-900">Hosting</a>
                <a href="{{ route('contact.show') }}" class="transition hover:text-slate-900">Contact</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('cart.index') }}" class="relative rounded-full p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    @if (($cartCount = app(\App\Services\Cart::class)->count()) > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-sky-600 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>

                @auth
                    <a href="{{ auth()->user()->isStaff() ? '/admin' : '/client' }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                        {{ auth()->user()->isStaff() ? 'Admin' : 'Client Portal' }}
                    </a>
                @else
                    <a href="/client/login" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Sign in</a>
                @endauth
            </div>
        </div>
    </header>

    @if (session('success') || session('error'))
        <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-slate-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <p class="text-lg font-bold text-white">Opti<span class="text-sky-500">Tide</span></p>
                <p class="mt-3 max-w-xs text-sm leading-6">
                    An Australian digital agency building websites, search visibility, and reliable hosting for businesses worldwide.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-slate-200">Services</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('services.index') }}" class="transition hover:text-white">Web Development</a></li>
                    <li><a href="{{ route('services.index') }}#seo" class="transition hover:text-white">SEO Plans</a></li>
                    <li><a href="{{ route('services.index') }}#smm" class="transition hover:text-white">Social Media Management</a></li>
                    <li><a href="{{ route('services.index') }}#hosting" class="transition hover:text-white">Web Hosting</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-slate-200">Get in touch</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('seo-audit.show') }}" class="transition hover:text-white">Free SEO audit</a></li>
                    <li><a href="{{ route('blog.index') }}" class="transition hover:text-white">Blog</a></li>
                    <li><a href="{{ route('contact.show') }}" class="transition hover:text-white">Contact us</a></li>
                    <li><a href="/client/login" class="transition hover:text-white">Client portal</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800">
            <div class="mx-auto max-w-7xl space-y-2 px-4 py-6 text-xs leading-5 sm:px-6 lg:px-8">
                @php($footerPages = \App\Models\CmsPage::footerPages())
                @if ($footerPages->isNotEmpty())
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        @foreach ($footerPages as $footerPage)
                            <a href="{{ route('cms.show', $footerPage->slug) }}" class="transition hover:text-white">{{ $footerPage->title }}</a>
                        @endforeach
                    </div>
                @endif
                <p>All prices are in Australian Dollars (AUD). No refunds for change of mind. This does not limit your rights under the Australian Consumer Law.</p>
                <p>&copy; {{ now()->year }} OptiTide. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
