@php
    $faqs = [
        ['q' => 'What does OptiTide do?', 'a' => 'OptiTide is an Australian-owned digital agency offering web design, SEO, social media management and web hosting — everything a business needs to win customers online, under one roof and one invoice.'],
        ['q' => 'How much does a website cost in Australia?', 'a' => 'Our websites start at $750 (one-time) for a Standard site, $1,500 for our most popular Pro build, and $2,500 for a fully bespoke Custom website. All prices are in AUD and include GST — the number you see is the number you pay.'],
        ['q' => 'Are there lock-in contracts?', 'a' => 'No. Website builds are one-time per project, and hosting is month-to-month — you can cancel any time. Every website we build is yours to keep.'],
        ['q' => 'Do you do SEO and social media as well as web design?', 'a' => 'Yes. We handle web design, SEO, social media management and hosting together, so everything works as one system instead of fighting each other. SEO plans start at $500 and social media from $250/month.'],
        ['q' => 'Do I own my website?', 'a' => 'Yes — the website is yours to keep, always, whether or not you stay on our hosting.'],
        ['q' => 'Is your pricing GST-inclusive?', 'a' => 'Yes. All prices are in Australian Dollars and include GST, so there are no surprises at checkout.'],
        ['q' => 'How long does a website take to build?', 'a' => 'Most starter sites launch in 2–3 weeks; larger bespoke builds vary with scope. We give you a clear timeline before we start.'],
        ['q' => 'What does the free SEO audit include?', 'a' => 'A personalised review of where your website currently ranks on Google, what is holding it back, and a plain-English plan to improve — at no cost and no obligation.'],
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($f) => [
            '@type' => 'Question', 'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ])->all(),
    ];
    $tabs = [
        ['id' => 'web', 'label' => 'Web Design', 'icon' => 'bi-window-desktop', 'plans' => $webTiers, 'pop' => 2],
        ['id' => 'seo', 'label' => 'SEO', 'icon' => 'bi-search', 'plans' => $seoPlans, 'pop' => 3],
        ['id' => 'smm', 'label' => 'Social Media', 'icon' => 'bi-megaphone', 'plans' => $smmPlans, 'pop' => 0],
        ['id' => 'hosting', 'label' => 'Hosting', 'icon' => 'bi-hdd-network', 'plans' => $hostingPlans, 'pop' => 2],
    ];
@endphp

<x-site-layout
    title="OptiTide — Web Design, SEO, Social Media & Hosting | Australian-Owned"
    description="Australian-owned digital agency: bespoke web design, SEO that ranks, social media management and reliable hosting. Free SEO audit, no lock-in, transparent AUD pricing (GST incl.)."
>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($faqSchema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>

    {{-- ═══ Hero ════════════════════════════════════════════════════════════ --}}
    <section class="hero-gradient text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-50"></div>
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background:radial-gradient(60% 80% at 78% 12%, rgba(34,211,238,.35), transparent 60%)"></div>
        <div class="container section position-relative pb-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="trust-pill eyebrow mb-3">🌊 Australian-owned · Web · SEO · Social · Hosting</span>
                    <h1 class="display-hero display-3 mb-3">
                        Websites that actually rank — designed, built &amp; hosted in <span class="text-gradient-ocean">Australia</span>.
                    </h1>
                    <p class="fs-5 text-white-50 mb-4" style="max-width:34rem">
                        One team for your website, your Google rankings, your social media and rock-solid hosting — so you can get back to running your business. No lock-in, ever.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-lg px-4"><i class="bi bi-search me-2"></i>Get my free SEO audit</a>
                        <a href="{{ route('home') }}#services" class="btn btn-outline-light btn-lg px-4">View services &amp; pricing</a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 small text-white-50">
                        <span><i class="bi bi-check-circle-fill text-info me-1"></i> No lock-in contracts</span>
                        <span><i class="bi bi-check-circle-fill text-info me-1"></i> AUD pricing (GST incl.)</span>
                        <span><i class="bi bi-check-circle-fill text-info me-1"></i> Real humans, fast replies</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative px-lg-4">
                        <div class="mockup">
                            <div class="mockup-bar d-flex align-items-center gap-2">
                                <span class="mockup-dot" style="background:#ef4444"></span>
                                <span class="mockup-dot" style="background:#f59e0b"></span>
                                <span class="mockup-dot" style="background:#22c55e"></span>
                                <span class="ms-2 bg-white rounded-pill small text-secondary px-3 py-1 flex-grow-1"><i class="bi bi-lock-fill me-1"></i>yourbusiness.com.au</span>
                            </div>
                            <div class="p-3">
                                <div class="rounded-3 mb-3 p-4 text-white" style="background:linear-gradient(135deg,#0e7490,#06b6d4)">
                                    <div class="bg-white bg-opacity-75 rounded-pill mb-2" style="height:10px;width:55%"></div>
                                    <div class="bg-white bg-opacity-50 rounded-pill mb-3" style="height:8px;width:75%"></div>
                                    <span class="badge bg-info text-dark">Shop now</span>
                                </div>
                                <div class="row g-2">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="col-4"><div class="border rounded-3 p-2"><div class="rounded-2 bg-light mb-2" style="height:34px"></div><div class="bg-light rounded-pill mb-1" style="height:6px;width:80%"></div><div class="bg-light rounded-pill" style="height:6px;width:55%"></div></div></div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <div class="float-card" style="top:-1rem;right:0">
                            <div class="d-flex align-items-center gap-2">
                                <span class="feature-ico bg-success bg-opacity-10 text-success" style="width:2.4rem;height:2.4rem;font-size:1.1rem"><i class="bi bi-graph-up-arrow"></i></span>
                                <div class="lh-1"><div class="fw-bold">SEO score 98</div><div class="small text-secondary">+41% traffic</div></div>
                            </div>
                        </div>
                        <div class="float-card" style="bottom:-1rem;left:0">
                            <div class="d-flex align-items-center gap-2">
                                <span class="feature-ico bg-primary bg-opacity-10 text-primary" style="width:2.4rem;height:2.4rem;font-size:1.1rem"><i class="bi bi-shield-check"></i></span>
                                <div class="lh-1"><div class="fw-bold">99.9% uptime</div><div class="small text-secondary">SSL · daily backups</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wave position-absolute bottom-0 start-0 w-100" aria-hidden="true">
            <svg viewBox="0 0 1440 70" preserveAspectRatio="none" style="height:54px"><path fill="#f8f9fa" d="M0,40 C240,72 480,6 720,28 C960,50 1200,80 1440,34 L1440,70 L0,70 Z"></path></svg>
        </div>
    </section>

    {{-- ═══ Service rotator (4 services) ════════════════════════════════════ --}}
    <section class="bg-light border-bottom">
        <div id="rotator" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner">
                @php
                    $slides = [
                        ['i' => 'bi-window-desktop', 't' => 'Web design that sells', 'd' => "Bespoke, mobile-first sites built around your customer's journey — never a template.", 'cta' => 'See web design plans'],
                        ['i' => 'bi-graph-up-arrow', 't' => 'SEO that gets you found', 'd' => 'Climb Google for the searches that actually bring you customers — proven with real ranking gains.', 'cta' => 'Get a free SEO audit'],
                        ['i' => 'bi-megaphone', 't' => 'Social media that stays on-brand', 'd' => 'Consistent posts and engagement across the platforms your customers use — done for you.', 'cta' => 'See social media plans'],
                        ['i' => 'bi-hdd-network', 't' => 'Hosting you never think about', 'd' => 'SSL, monitoring, updates and daily backups handled for you. 99.9% uptime target, no lock-in.', 'cta' => 'See hosting plans'],
                    ];
                @endphp
                @foreach ($slides as $s)
                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        <div class="container py-5">
                            <div class="row align-items-center g-4 justify-content-center text-center text-md-start">
                                <div class="col-auto"><span class="feature-ico text-white" style="width:4rem;height:4rem;font-size:2rem;background:linear-gradient(135deg,#0e7490,#22d3ee)"><i class="bi {{ $s['i'] }}"></i></span></div>
                                <div class="col-md"><h2 class="h3 fw-bold mb-1">{{ $s['t'] }}</h2><p class="text-secondary mb-0" style="max-width:40rem">{{ $s['d'] }}</p></div>
                                <div class="col-md-auto"><a href="{{ route('home') }}#services" class="btn btn-outline-primary">{{ $s['cta'] }} <i class="bi bi-arrow-right ms-1"></i></a></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="carousel-indicators" style="bottom:-.25rem">
                @for ($i = 0; $i < 4; $i++)<button type="button" data-bs-target="#rotator" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" style="background-color:#0e7490" aria-label="Slide {{ $i + 1 }}"></button>@endfor
            </div>
        </div>
    </section>

    {{-- ═══ Trust strip ═════════════════════════════════════════════════════ --}}
    <section class="border-bottom bg-light">
        <div class="container py-4">
            <div class="row align-items-center g-3 justify-content-center text-center text-lg-start">
                <div class="col-lg-auto">
                    <span class="text-warning">★★★★★</span> <span class="fw-semibold ms-1">Rated 5.0</span>
                    <span class="text-secondary small"><i class="bi bi-google ms-1 me-1"></i>on Google</span>
                </div>
                <div class="col">
                    {{-- TODO: replace placeholders with real client logos --}}
                    <div class="row row-cols-3 row-cols-md-5 g-3 align-items-center">
                        @for ($i = 0; $i < 5; $i++)<div class="col"><div class="logo-placeholder"></div></div>@endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Stats band (count-up) ═══════════════════════════════════════════ --}}
    <section class="section--deep text-white">
        <div class="container py-5">
            <div class="row text-center g-4">
                <div class="col-6 col-md-3"><i class="bi bi-activity text-info fs-3"></i><div class="stat-num mt-2" data-to="99.9" data-suf="%" data-dec="1">99.9%</div><div class="small text-white-50">Uptime target</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-geo-alt-fill text-info fs-3"></i><div class="stat-num mt-2" data-to="100" data-suf="%">100%</div><div class="small text-white-50">Australian-owned</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-slash-circle text-info fs-3"></i><div class="stat-num mt-2" data-to="0">0</div><div class="small text-white-50">Lock-in contracts</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-grid-1x2-fill text-info fs-3"></i><div class="stat-num mt-2" data-to="4">4</div><div class="small text-white-50">Services, one team</div></div>
            </div>
        </div>
    </section>

    {{-- ═══ Why OptiTide (4 services → blog) ════════════════════════════════ --}}
    <section class="section bg-mesh">
        <div class="container">
            <div class="text-center mx-auto mb-5 reveal" style="max-width:38rem">
                <p class="eyebrow text-primary"><i class="bi bi-stars me-1"></i> Why OptiTide</p>
                <h2 class="fw-bold display-6">Everything you need to <span class="text-gradient-ocean">win online</span></h2>
                <p class="text-secondary">Web design, SEO, social media and hosting — one Australian team that makes them all work together.</p>
            </div>
            <div class="row g-4">
                @php
                    $props = [
                        ['t' => 'Web design that converts', 'd' => 'Bespoke, fast, mobile-first websites built to turn visitors into paying customers — never a template.', 'g' => 'linear-gradient(135deg,#0e7490,#22d3ee)', 'i' => 'bi-window-stack'],
                        ['t' => 'SEO that ranks', 'd' => 'Climb Google for the searches that matter, with content and technical SEO that actually moves the needle.', 'g' => 'linear-gradient(135deg,#0d9488,#22d3ee)', 'i' => 'bi-graph-up-arrow'],
                        ['t' => 'Social media management', 'd' => 'On-brand content and engagement across the platforms your customers use — planned and posted for you.', 'g' => 'linear-gradient(135deg,#0891b2,#67e8f9)', 'i' => 'bi-megaphone'],
                        ['t' => 'Hosting that never sleeps', 'd' => 'SSL, monitoring, updates and daily backups handled for you — 99.9% uptime target, zero headaches.', 'g' => 'linear-gradient(135deg,#155e75,#2dd4bf)', 'i' => 'bi-hdd-network'],
                    ];
                @endphp
                @foreach ($props as $prop)
                    <div class="col-sm-6 col-lg-3 reveal">
                        <div class="card card-lift h-100 border-0 shadow-sm rounded-4 p-2">
                            <div class="card-body d-flex flex-column">
                                <span class="feature-ico text-white mb-3" style="background:{{ $prop['g'] }}"><i class="bi {{ $prop['i'] }}"></i></span>
                                <h3 class="h5 fw-bold">{{ $prop['t'] }}</h3>
                                <p class="text-secondary small flex-grow-1">{{ $prop['d'] }}</p>
                                <a href="{{ route('blog.index') }}" class="fw-semibold text-decoration-none small mt-2">Learn more <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ Feature showcases (Design / SEO / Social / Hosting) ═════════════ --}}
    <section class="section">
        <div class="container d-grid gap-5">
            {{-- Design --}}
            <div class="row align-items-center g-5 reveal">
                <div class="col-lg-6">
                    <span class="feature-ico bg-primary bg-opacity-10 text-primary mb-3"><i class="bi bi-window-desktop"></i></span>
                    <h2 class="fw-bold h1">Websites designed to sell</h2>
                    <p class="text-secondary fs-5">Every pixel earns its place. We design around your customer's journey so more visitors become buyers — bespoke, fast, and built for your brand.</p>
                    <ul class="list-unstyled d-grid gap-2 mb-4">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Conversion-focused layouts &amp; copy</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Lightning-fast, mobile-first builds</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Your brand — never a template</li>
                    </ul>
                    <a href="{{ route('home') }}#services" class="btn btn-primary">Explore web design <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
                <div class="col-lg-6">
                    <div class="rounded-4 p-4 p-md-5 bg-light border">
                        <div class="rounded-3 p-4 text-white mb-3" style="background:linear-gradient(135deg,#0e7490,#06b6d4)">
                            <div class="fw-bold fs-5 mb-1">Grow Co.</div><div class="bg-white bg-opacity-50 rounded-pill mb-3" style="height:8px;width:70%"></div><span class="badge bg-info text-dark">Get started</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><div class="bg-white border rounded-3 p-3"><i class="bi bi-cart-check text-primary fs-4"></i><div class="small fw-semibold mt-1">Online store</div></div></div>
                            <div class="col-6"><div class="bg-white border rounded-3 p-3"><i class="bi bi-people text-success fs-4"></i><div class="small fw-semibold mt-1">More leads</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- SEO --}}
            <div class="row align-items-center g-5 flex-lg-row-reverse reveal">
                <div class="col-lg-6">
                    <span class="feature-ico bg-success bg-opacity-10 text-success mb-3"><i class="bi bi-search"></i></span>
                    <h2 class="fw-bold h1">Get found on Google</h2>
                    <p class="text-secondary fs-5">We put your business in front of people actively searching for what you sell — with keyword research, technical SEO and content that earns real ranking gains.</p>
                    <ul class="list-unstyled d-grid gap-2 mb-4">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Keyword &amp; competitor research</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Technical + on-page SEO</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Content that earns rankings</li>
                    </ul>
                    <a href="{{ route('seo-audit.show') }}" class="btn btn-success">Get a free SEO audit <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
                <div class="col-lg-6">
                    <div class="rounded-4 p-4 p-md-5 bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-3"><span class="fw-semibold"><i class="bi bi-bar-chart-line text-success me-1"></i>Ranking growth</span><span class="badge text-bg-success">+41%</span></div>
                        @php $ranks = [['plumber melbourne','92%'],['emergency plumber','78%'],['hot water repair','64%']]; @endphp
                        @foreach ($ranks as $r)
                            <div class="mb-2"><div class="d-flex justify-content-between small mb-1"><span>{{ $r[0] }}</span><span class="text-success fw-semibold">#{{ $loop->iteration }}</span></div><div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $r[1] }}"></div></div></div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Social media --}}
            <div class="row align-items-center g-5 reveal">
                <div class="col-lg-6">
                    <span class="feature-ico bg-info bg-opacity-10 text-info mb-3"><i class="bi bi-megaphone"></i></span>
                    <h2 class="fw-bold h1">Social media, sorted</h2>
                    <p class="text-secondary fs-5">Stay top of mind without lifting a finger. We plan, create and post on-brand content across the platforms your customers actually use — and keep them engaged.</p>
                    <ul class="list-unstyled d-grid gap-2 mb-4">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Content calendar &amp; scheduling</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>On-brand posts across 2–4 platforms</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Engagement monitoring &amp; reporting</li>
                    </ul>
                    <a href="{{ route('home') }}#services" class="btn btn-info text-white">See social plans <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
                <div class="col-lg-6">
                    <div class="rounded-4 p-4 p-md-5 bg-light border">
                        <div class="bg-white border rounded-3 p-3">
                            <div class="d-flex align-items-center gap-2 mb-2"><span class="avatar-initials" style="width:2.2rem;height:2.2rem;font-size:.8rem;background:#0e7490">OT</span><div class="lh-1"><div class="fw-semibold small">OptiTide</div><div class="text-secondary" style="font-size:.7rem">Just now</div></div></div>
                            <div class="bg-light rounded-pill mb-1" style="height:8px;width:90%"></div>
                            <div class="bg-light rounded-pill mb-3" style="height:8px;width:65%"></div>
                            <div class="d-flex gap-3 text-secondary small"><span><i class="bi bi-heart-fill text-danger"></i> 128</span><span><i class="bi bi-chat"></i> 24</span><span><i class="bi bi-share"></i> 12</span></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Hosting --}}
            <div class="row align-items-center g-5 flex-lg-row-reverse reveal">
                <div class="col-lg-6">
                    <span class="feature-ico bg-primary bg-opacity-10 text-primary mb-3"><i class="bi bi-hdd-network"></i></span>
                    <h2 class="fw-bold h1">Hosting you never worry about</h2>
                    <p class="text-secondary fs-5">We keep your site fast, secure and online — with monitoring and backups running around the clock so you don't have to think about it.</p>
                    <ul class="list-unstyled d-grid gap-2 mb-4">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Free SSL &amp; 99.9% uptime target</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Daily backups (Managed)</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Uptime &amp; SSL monitoring</li>
                    </ul>
                    <a href="{{ route('home') }}#services" class="btn btn-primary">See hosting plans <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
                <div class="col-lg-6">
                    <div class="rounded-4 p-4 p-md-5 bg-light border">
                        <div class="d-grid gap-2">
                            <div class="bg-white border rounded-3 p-3 d-flex justify-content-between align-items-center"><span><i class="bi bi-check-circle-fill text-success me-2"></i>All systems operational</span><span class="badge text-bg-success">Live</span></div>
                            <div class="bg-white border rounded-3 p-3 d-flex justify-content-between align-items-center"><span><i class="bi bi-shield-lock-fill text-primary me-2"></i>SSL certificate</span><span class="small text-success fw-semibold">Valid</span></div>
                            <div class="bg-white border rounded-3 p-3 d-flex justify-content-between align-items-center"><span><i class="bi bi-clock-history text-info me-2"></i>Last backup</span><span class="small text-secondary">2 hours ago</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ All services & pricing (tabbed) ════════════════════════════════ --}}
    <section class="section bg-light" id="services">
        <div class="container">
            <div class="text-center mx-auto mb-5 reveal" style="max-width:42rem">
                <p class="eyebrow text-primary"><i class="bi bi-grid-1x2 me-1"></i> Services &amp; pricing</p>
                <h2 class="fw-bold display-6">Everything your business needs, one team</h2>
                <p class="text-secondary">Web design, SEO, social media and hosting — transparent AUD pricing, GST included, no lock-in.</p>
            </div>
            <ul class="nav nav-pills justify-content-center flex-wrap gap-2 mb-5" role="tablist">
                @foreach ($tabs as $t)
                    <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }} rounded-pill px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tab-{{ $t['id'] }}" type="button" role="tab"><i class="bi {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}</button></li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach ($tabs as $t)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $t['id'] }}" role="tabpanel">
                        <div class="row g-4 justify-content-center">
                            @foreach ($t['plans'] as $p)
                                @include('storefront._plan-card', ['product' => $p, 'popular' => $loop->iteration === $t['pop']])
                            @endforeach
                        </div>
                        @if ($t['id'] === 'smm')
                            <p class="text-center text-secondary small mt-4"><i class="bi bi-info-circle me-1"></i>Social media is also bundled into our Tier 2–4 SEO plans for even better value.</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="text-center text-secondary small mt-4"><i class="bi bi-shield-check text-success me-1"></i>No lock-in — every build is yours to keep, cancel hosting any time.</p>
        </div>
    </section>

    {{-- ═══ Everything included ════════════════════════════════════════════ --}}
    <section class="section">
        <div class="container" style="max-width:52rem">
            <div class="text-center mx-auto mb-5 reveal" style="max-width:40rem">
                <p class="eyebrow text-primary"><i class="bi bi-people me-1"></i> Everything included</p>
                <h2 class="fw-bold display-6">One team. One invoice.</h2>
                <p class="text-secondary">No hidden add-ons — SSL, hosting and support are included, not upsold.</p>
            </div>
            <div class="table-responsive rounded-4 shadow-sm overflow-hidden border reveal">
                <table class="table align-middle mb-0 bg-white">
                    <thead><tr class="section--deep text-white"><th class="py-3 ps-4">What you get</th><th class="py-3 text-center">With OptiTide</th><th class="py-3 text-center pe-4">Buying separately</th></tr></thead>
                    <tbody>
                        @foreach (['Custom, on-brand web design', 'Conversion copywriting', 'SEO setup &amp; strategy', 'Social media management', 'Hosting + free SSL', 'Uptime &amp; backup monitoring', 'One team, one invoice', 'No lock-in contract'] as $row)
                            <tr><td class="ps-4 fw-medium">{!! $row !!}</td><td class="text-center"><i class="bi bi-check-circle-fill text-success fs-5"></i></td><td class="text-center pe-4"><span class="text-secondary small"><i class="bi bi-x-circle text-danger me-1"></i>$ extra</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═══ How it works ═══════════════════════════════════════════════════ --}}
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5 reveal" style="max-width:38rem">
                <p class="eyebrow text-primary"><i class="bi bi-compass me-1"></i> How it works</p>
                <h2 class="fw-bold display-6">Live in four simple steps</h2>
            </div>
            <div class="row g-4">
                @php
                    $steps = [
                        ['n' => 1, 'i' => 'bi-search', 'c' => 'primary', 't' => 'Free SEO audit', 'd' => 'See where you rank — no cost, no obligation.'],
                        ['n' => 2, 'i' => 'bi-pencil-square', 'c' => 'success', 't' => 'Plan & design', 'd' => 'We map the right mix and design around your brand.'],
                        ['n' => 3, 'i' => 'bi-rocket-takeoff', 'c' => 'info', 't' => 'Build & launch', 'd' => 'We build, test and get you live — fast.'],
                        ['n' => 4, 'i' => 'bi-graph-up', 'c' => 'primary', 't' => 'Grow', 'd' => 'SEO, social, hosting and support keep you climbing.'],
                    ];
                @endphp
                @foreach ($steps as $step)
                    <div class="col-md-6 col-lg-3 reveal">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-2 card-lift">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-2"><span class="step-num bg-{{ $step['c'] }} text-white">{{ $step['n'] }}</span><i class="bi {{ $step['i'] }} fs-3 text-{{ $step['c'] }}"></i></div>
                                <h3 class="h6 fw-bold mt-2">{{ $step['t'] }}</h3>
                                <p class="text-secondary small mb-0">{{ $step['d'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ Australian reach (city pages) + guarantees ═════════════════════ --}}
    <section class="section section--deep text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-25"></div>
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="glass rounded-4 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-semibold text-white"><i class="bi bi-broadcast text-info me-2"></i>Serving businesses Australia-wide</span>
                            <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-circle-fill me-1"></i>Online</span>
                        </div>
                        <div class="row g-2">
                            @foreach (array_slice(config('locations.cities', []), 0, 6) as $c)
                                <div class="col-6">
                                    <a href="{{ route('location.show', $c['slug']) }}" class="d-flex align-items-center gap-2 rounded-3 px-3 py-2 text-decoration-none link-light" style="background:rgba(255,255,255,.06)">
                                        <span class="d-inline-block rounded-circle bg-info reach-ping" style="width:.6rem;height:.6rem"></span>
                                        <span class="small">{{ $c['name'] }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <p class="eyebrow text-info"><i class="bi bi-geo-alt-fill me-1"></i> Australian-owned</p>
                    <h2 class="fw-bold display-6">Built in Australia, for businesses that mean business.</h2>
                    <p class="text-white-50">We're a local team — you talk to real people who own the work, on your timezone. No overseas call centres, no lock-in, no nonsense.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <span class="trust-pill"><i class="bi bi-geo-alt-fill text-info"></i>Australian-owned</span>
                        <span class="trust-pill"><i class="bi bi-slash-circle text-info"></i>No lock-in</span>
                        <span class="trust-pill"><i class="bi bi-arrow-repeat text-info"></i>Free migration</span>
                        <span class="trust-pill"><i class="bi bi-headset text-info"></i>Real human support</span>
                        <span class="trust-pill"><i class="bi bi-shield-check text-info"></i>SSL + backups included</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Lead magnet — free SEO audit ═══════════════════════════════════ --}}
    <section class="section">
        <div class="container">
            <div class="hero-gradient text-white rounded-4 p-4 p-md-5 shadow-lg position-relative overflow-hidden reveal">
                <div class="position-absolute top-0 end-0 h-100 dotted opacity-50" style="width:40%"></div>
                <div class="row align-items-center g-4 position-relative">
                    <div class="col-lg-7">
                        <h2 class="fw-bold mb-2"><i class="bi bi-search me-2"></i>Not ranking on Google? Find out why — free.</h2>
                        <p class="text-white-50 mb-0">Enter your site and get a personalised audit + a plain-English plan to outrank your competitors. No cost, no obligation.</p>
                    </div>
                    <div class="col-lg-5">
                        <form action="{{ route('seo-audit.show') }}" method="GET">
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-0"><i class="bi bi-globe text-secondary"></i></span>
                                <input type="url" name="url" class="form-control border-0" placeholder="yourbusiness.com.au" aria-label="Your website URL">
                                <button class="btn btn-accent fw-semibold px-3" type="submit">Get my free audit</button>
                            </div>
                        </form>
                        <div class="small text-white-50 mt-2"><i class="bi bi-shield-lock me-1"></i>We never share your details · one audit per day</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Recent work — coming soon ══════════════════════════════════════ --}}
    <section class="section bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5 reveal" style="max-width:38rem">
                <p class="eyebrow text-primary"><i class="bi bi-collection me-1"></i> Recent work</p>
                <h2 class="fw-bold display-6">Case studies — coming soon</h2>
                <p class="text-secondary">We're a fresh Australian agency, and our first projects are underway. Want to be one of our featured success stories?</p>
                <a href="{{ route('seo-audit.show') }}" class="btn btn-accent mt-2"><i class="bi bi-search me-1"></i>Start with a free SEO audit</a>
            </div>
            <div class="row g-4">
                @php $ph = ['linear-gradient(135deg,#0e7490,#06b6d4)','linear-gradient(135deg,#0d9488,#22d3ee)','linear-gradient(135deg,#155e75,#2dd4bf)']; @endphp
                @foreach ($ph as $g)
                    <div class="col-md-4 reveal">
                        <div class="work-tile shadow-sm justify-content-center align-items-center text-center" style="background:{{ $g }}">
                            <div><i class="bi bi-hourglass-split fs-3"></i><div class="fw-semibold mt-1">Coming soon</div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ What clients say ═══════════════════════════════════════════════ --}}
    <section class="section section--deep text-white position-relative overflow-hidden text-center">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-25"></div>
        <div class="container position-relative" style="max-width:40rem">
            <p class="eyebrow text-info"><i class="bi bi-chat-quote me-1"></i> What clients say</p>
            <h2 class="fw-bold display-6 mb-3">Be our first 5-star review</h2>
            <div class="text-warning fs-3 mb-3">★★★★★</div>
            <p class="fs-5 text-white-50 mb-4">We're just getting started, and we're building our reputation one happy client at a time. Your success story could be the first one featured right here.</p>
            <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-lg px-4">Work with us — start free</a>
        </div>
    </section>

    {{-- ═══ FAQ (rich for AI/SEO — FAQPage schema in head) ═════════════════ --}}
    <section class="section">
        <div class="container" style="max-width:46rem">
            <div class="text-center mb-5 reveal">
                <p class="eyebrow text-primary"><i class="bi bi-patch-question me-1"></i> FAQ</p>
                <h2 class="fw-bold display-6">Questions, answered</h2>
            </div>
            <div class="accordion accordion-flush" id="faq">
                @foreach ($faqs as $i => $faq)
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }} fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq{{ $i }}">{{ $faq['q'] }}</button>
                        </h3>
                        <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faq">
                            <div class="accordion-body text-secondary">{{ $faq['a'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ Final CTA ══════════════════════════════════════════════════════ --}}
    <section class="section hero-gradient text-white position-relative overflow-hidden text-center">
        <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-25"></div>
        <div class="container position-relative" style="max-width:44rem">
            <i class="bi bi-water text-info fs-1 mb-2"></i>
            <h2 class="fw-bold display-5 mb-3">Ready to rank? Get your free SEO audit.</h2>
            <p class="fs-5 text-white-50 mb-4">Tell us what you need and we'll recommend the right mix of web design, SEO, social media and hosting — Australian-owned, no lock-in.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="{{ route('seo-audit.show') }}" class="btn btn-accent btn-lg px-4">Get my free audit</a>
                <a href="{{ route('contact.show') }}" class="btn btn-outline-light btn-lg px-4">Talk to us</a>
            </div>
        </div>
    </section>

</x-site-layout>
