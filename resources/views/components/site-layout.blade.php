@props([
    'title' => 'OptiTide — Web Design, SEO & Hosting',
    'description' => 'An Australian digital agency building websites, search visibility, and reliable hosting for businesses worldwide.',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'head' => null,
])
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='88'>🌊</text></svg>">
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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/scss/store.scss', 'resources/js/store.js'])

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
<body class="bg-white d-flex flex-column min-vh-100">

    {{-- Conversion: lead-magnet announcement bar --}}
    <div class="section--deep text-white text-center small py-2">
        <div class="container d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3">
            <span><i class="bi bi-geo-alt-fill text-info me-1"></i>Australian-owned</span>
            <span class="opacity-50 d-none d-md-inline">·</span>
            <span><i class="bi bi-shield-check text-info me-1"></i>No lock-in contracts</span>
            <span class="opacity-50 d-none d-md-inline">·</span>
            <a href="{{ route('seo-audit.show') }}" class="text-white fw-semibold text-decoration-none"><i class="bi bi-stars text-warning me-1"></i>Free SEO audit — see where you rank &rarr;</a>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top py-3 navbar-ocean" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand fs-4 fw-bold d-flex align-items-center text-white" href="{{ route('home') }}"><span class="me-2" style="font-size:1.35rem;line-height:1">🌊</span>Opti<span class="text-info">Tide</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1 text-center">
                    <li class="nav-item"><a class="nav-link fw-medium px-3" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-medium px-3" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Services</a>
                        <ul class="dropdown-menu shadow border-0 rounded-3 mt-2">
                            <li><a class="dropdown-item py-2" href="{{ route('home') }}#services"><i class="bi bi-window-desktop text-primary me-2"></i>Web Design</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('home') }}#services"><i class="bi bi-search text-success me-2"></i>SEO</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('home') }}#services"><i class="bi bi-megaphone text-info me-2"></i>Social Media</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('home') }}#services"><i class="bi bi-hdd-network text-primary me-2"></i>Hosting</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('seo-audit.show') }}"><i class="bi bi-stars text-warning me-2"></i>Free SEO audit</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link fw-medium px-3" href="{{ route('home') }}#services">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium px-3" href="{{ route('blog.index') }}">Blog</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium px-3" href="{{ route('contact.show') }}">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap py-2 py-lg-0">
                    <a class="btn btn-outline-light position-relative" href="{{ route('cart.index') }}" aria-label="Cart">
                        <i class="bi bi-cart3"></i>
                        @if (($cartCount = app(\App\Services\Cart::class)->count()) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-white" style="background-color:#ff7a59!important">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a class="btn btn-accent fw-semibold" href="{{ route('seo-audit.show') }}"><i class="bi bi-search me-1"></i>Free SEO audit</a>
                    @auth
                        <a class="btn btn-outline-light fw-semibold" href="{{ auth()->user()->isStaff() ? '/admin' : '/client' }}">{{ auth()->user()->isStaff() ? 'Admin' : 'Client Portal' }}</a>
                    @else
                        <a class="btn btn-outline-light fw-semibold" href="/client/login">Client login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    @if (session('success') || session('error'))
        <div class="container mt-3">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    <main class="flex-grow-1">
        {{ $slot }}
    </main>

    <footer class="bg-dark text-secondary pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <p class="fs-4 fw-bold text-white mb-2">Opti<span class="text-primary">Tide</span></p>
                    <p class="small" style="max-width:22rem">An Australian digital agency building websites, search visibility, and reliable hosting for businesses worldwide.</p>
                    <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-sm mt-2">Get a free SEO audit &rarr;</a>
                    {{-- TODO: point these at your real social profiles --}}
                    <div class="d-flex gap-2 mt-3 fs-5">
                        <a href="#" class="link-light" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="link-light" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="link-light" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="link-light" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="text-uppercase text-white-50 fw-semibold small mb-3" style="letter-spacing:.12em">Services</p>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="{{ route('home') }}#services" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Web Design</a></li>
                        <li><a href="{{ route('home') }}#services" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">SEO</a></li>
                        <li><a href="{{ route('home') }}#services" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Social Media</a></li>
                        <li><a href="{{ route('home') }}#services" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Hosting</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-4">
                    <p class="text-uppercase text-white-50 fw-semibold small mb-3" style="letter-spacing:.12em">Get in touch</p>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="{{ route('seo-audit.show') }}" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Free SEO audit</a></li>
                        <li><a href="{{ route('blog.index') }}" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Blog</a></li>
                        <li><a href="{{ route('contact.show') }}" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Contact us</a></li>
                        <li><a href="/client/login" class="link-light link-underline-opacity-0 link-underline-opacity-75-hover">Client portal</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-4">
            {{-- Local SEO internal links --}}
            <p class="text-uppercase text-white-50 small mb-2" style="letter-spacing:.12em">Web design near you</p>
            <div class="d-flex flex-wrap gap-3 small mb-4">
                @foreach (config('locations.cities', []) as $c)
                    <a href="{{ route('location.show', $c['slug']) }}" class="link-secondary link-underline-opacity-0 link-underline-opacity-75-hover">Web Design {{ $c['name'] }}</a>
                @endforeach
            </div>
            <div class="small text-secondary">
                @php($footerPages = \App\Models\CmsPage::footerPages())
                @if ($footerPages->isNotEmpty())
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        @foreach ($footerPages as $footerPage)
                            <a href="{{ route('cms.show', $footerPage->slug) }}" class="link-secondary link-underline-opacity-0 link-underline-opacity-75-hover">{{ $footerPage->title }}</a>
                        @endforeach
                    </div>
                @endif
                <p class="mb-1">All prices are in Australian Dollars (AUD). No refunds for change of mind. This does not limit your rights under the Australian Consumer Law.</p>
                <p class="mb-0">&copy; {{ now()->year }} OptiTide. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Persistent conversion overlays --}}
    {{-- Desktop: floating "chat" launcher → contact (honest; no fake AI widget) --}}
    <a href="{{ route('contact.show') }}" class="d-none d-lg-inline-flex align-items-center justify-content-center btn btn-primary rounded-circle shadow position-fixed" style="width:3.5rem;height:3.5rem;right:1.25rem;bottom:1.25rem;z-index:1035" aria-label="Chat with our team" title="Chat with our team — fast replies">
        <i class="bi bi-chat-dots-fill fs-5"></i>
    </a>
    {{-- Mobile: sticky CTA bar (spacer keeps it off the footer) --}}
    <div class="d-lg-none" style="height:4.75rem"></div>
    <div class="d-lg-none fixed-bottom bg-white border-top shadow p-2" style="z-index:1035">
        <div class="container d-flex gap-2">
            <a href="{{ route('seo-audit.show') }}" class="btn btn-accent flex-fill fw-semibold"><i class="bi bi-search me-1"></i>Free audit</a>
            <a href="{{ route('contact.show') }}" class="btn btn-outline-primary flex-fill fw-semibold">Contact</a>
        </div>
    </div>
</body>
</html>
