@php
    $city = $location['name'];
    $state = $location['state'];
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => "Web Design, SEO, Social Media & Hosting in {$city}",
        'serviceType' => 'Web design, SEO, social media management and web hosting',
        'areaServed' => ['@type' => 'City', 'name' => $city, 'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => $state]],
        'provider' => ['@type' => 'Organization', 'name' => config('company.legal_name', 'OptiTide'), 'url' => rtrim(config('app.url'), '/')],
        'url' => url()->current(),
    ];
@endphp

<x-site-layout
    :title="'Web Design ' . $city . ' — SEO, Social Media & Hosting | OptiTide'"
    :description="'Australian-owned web design, SEO, social media & hosting for ' . $city . ', ' . $state . ' businesses. Bespoke websites that rank on Google. Free SEO audit — no lock-in.'"
>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>

    {{-- Hero --}}
    <section class="hero-gradient text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-50"></div>
        <div class="container section position-relative text-center" style="max-width:48rem">
            <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="link-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $city }}</li>
                </ol>
            </nav>
            <span class="trust-pill eyebrow mb-3"><i class="bi bi-geo-alt-fill text-info me-1"></i>{{ $city }}, {{ $location['abbr'] }}</span>
            <h1 class="display-hero display-4 fw-bold mb-3">Web Design, SEO, Social Media &amp; Hosting in <span class="text-gradient-ocean">{{ $city }}</span></h1>
            <p class="fs-5 text-white-50 mx-auto mb-4" style="max-width:36rem">
                Australian-owned digital agency helping {{ $city }} businesses win more customers online — bespoke websites, SEO that ranks, social media, and rock-solid hosting. No lock-in, ever.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-lg px-4"><i class="bi bi-search me-2"></i>Get my free {{ $city }} SEO audit</a>
                <a href="{{ route('home') }}#services" class="btn btn-outline-light btn-lg px-4">View services</a>
            </div>
        </div>
        <div class="wave position-absolute bottom-0 start-0 w-100" aria-hidden="true">
            <svg viewBox="0 0 1440 70" preserveAspectRatio="none" style="height:54px"><path fill="#fff" d="M0,40 C240,72 480,6 720,28 C960,50 1200,80 1440,34 L1440,70 L0,70 Z"></path></svg>
        </div>
    </section>

    {{-- Local intro --}}
    <section class="section">
        <div class="container" style="max-width:46rem">
            <div class="text-center mx-auto mb-4">
                <p class="eyebrow text-primary"><i class="bi bi-buildings me-1"></i> {{ $state }}</p>
                <h2 class="fw-bold display-6">Helping {{ $city }} businesses grow online</h2>
            </div>
            <p class="lead text-secondary">{{ $location['intro'] }} OptiTide gives {{ $city }} businesses everything they need to compete — a beautiful, fast website, search rankings that bring real enquiries, active social media, and hosting that never lets you down.</p>
            <p class="text-secondary">Because we're Australian-owned, you deal with real people on your timezone — no overseas call centres, no lock-in contracts, and transparent AUD pricing (GST included). Whether you're a trade, a clinic, a café or a professional firm in {{ $city }}, {{ $state }}, we build the online presence that turns searchers into customers.</p>
        </div>
    </section>

    {{-- Services overview --}}
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width:38rem">
                <p class="eyebrow text-primary"><i class="bi bi-grid me-1"></i> What we do in {{ $city }}</p>
                <h2 class="fw-bold display-6">Everything under one roof</h2>
            </div>
            <div class="row g-4 justify-content-center">
                @php
                    $svc = [
                        ['i' => 'bi-window-desktop', 'g' => 'linear-gradient(135deg,#0e7490,#22d3ee)', 't' => 'Web Design', 'd' => "Bespoke, mobile-first websites built to convert {$city} visitors into customers."],
                        ['i' => 'bi-search', 'g' => 'linear-gradient(135deg,#0d9488,#22d3ee)', 't' => 'SEO', 'd' => "Rank on Google for the searches {$city} customers actually use."],
                        ['i' => 'bi-megaphone', 'g' => 'linear-gradient(135deg,#0891b2,#67e8f9)', 't' => 'Social Media', 'd' => 'Consistent, on-brand social content that keeps you front of mind.'],
                        ['i' => 'bi-hdd-network', 'g' => 'linear-gradient(135deg,#155e75,#2dd4bf)', 't' => 'Hosting', 'd' => 'Fast, secure, monitored hosting with SSL and daily backups.'],
                    ];
                @endphp
                @foreach ($svc as $s)
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-lift h-100 border-0 shadow-sm rounded-4 text-center p-2">
                            <div class="card-body">
                                <span class="feature-ico text-white mb-3 mx-auto" style="background:{{ $s['g'] }}"><i class="bi {{ $s['i'] }}"></i></span>
                                <h3 class="h5 fw-bold">{{ $s['t'] }}</h3>
                                <p class="text-secondary small mb-0">{{ $s['d'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Web design pricing --}}
    <section class="section">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width:40rem">
                <p class="eyebrow text-primary"><i class="bi bi-tags me-1"></i> {{ $city }} web design</p>
                <h2 class="fw-bold display-6">Transparent pricing</h2>
                <p class="text-secondary">One-time AUD pricing, GST included — no surprises, no lock-in.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach ($webTiers as $tier)
                    @php $popular = $loop->iteration === 2; @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-4 p-2 bg-white {{ $popular ? 'pricing-popular' : 'shadow-sm card-lift' }}">
                            @if ($popular)<span class="ribbon"><i class="bi bi-star-fill me-1"></i>MOST POPULAR</span>@endif
                            <div class="card-body d-flex flex-column">
                                <h3 class="h4 fw-bold mb-1">{{ $tier->name }}</h3>
                                <p class="text-secondary small">{{ $tier->description }}</p>
                                <div class="my-3"><span class="display-6 fw-bold">{{ $tier->price->format() }}</span> <span class="text-secondary small">one-time · GST incl.</span></div>
                                <ul class="list-unstyled d-grid gap-2 mb-4 flex-grow-1 text-start">
                                    @foreach ($tier->features ?? [] as $feature)
                                        <li class="d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>{{ $feature }}</span></li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('services.show', $tier) }}" class="btn {{ $popular ? 'btn-accent' : 'btn-outline-primary' }} btn-lg mt-auto">Choose {{ $tier->name }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why this city --}}
    <section class="section section--deep text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-25"></div>
        <div class="container position-relative" style="max-width:44rem">
            <div class="text-center mb-4">
                <p class="eyebrow text-info"><i class="bi bi-heart me-1"></i> Local &amp; Australian-owned</p>
                <h2 class="fw-bold display-6">Why {{ $city }} businesses choose OptiTide</h2>
            </div>
            <div class="row g-3">
                @foreach (['Local team on your timezone — no overseas call centres', 'No lock-in contracts, cancel hosting any time', 'Transparent AUD pricing (GST included)', 'Design, SEO, social &amp; hosting under one roof', 'Free SEO audit — see where you rank in ' . $city, 'Real humans who own your project end to end'] as $point)
                    <div class="col-md-6">
                        <div class="d-flex gap-2 align-items-start"><i class="bi bi-check-circle-fill text-info mt-1"></i><span>{!! $point !!}</span></div>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-lg px-4">Get my free {{ $city }} SEO audit <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </section>

    {{-- Other locations (internal links) --}}
    <section class="section">
        <div class="container text-center" style="max-width:46rem">
            <p class="eyebrow text-primary"><i class="bi bi-geo me-1"></i> Also serving</p>
            <h2 class="fw-bold display-6 mb-4">Web design across Australia</h2>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                @foreach ($others as $c)
                    <a href="{{ route('location.show', $c['slug']) }}" class="btn btn-outline-primary btn-sm rounded-pill">{{ $c['name'] }}</a>
                @endforeach
            </div>
        </div>
    </section>
</x-site-layout>
