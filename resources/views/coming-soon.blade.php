<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='88'>🌊</text></svg>">
    <title>OptiTide — Launching soon</title>
    <meta name="description" content="OptiTide — Australian web design, SEO & hosting. Our new home launches soon. Get a free SEO audit while we build.">
    <meta name="robots" content="noindex">
    <meta property="og:title" content="OptiTide — Launching soon">
    <meta property="og:description" content="Australian web design, SEO & hosting — launching soon.">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/scss/store.scss', 'resources/js/store.js'])
</head>
<body class="hero-gradient text-white min-vh-100 d-flex flex-column position-relative overflow-hidden">

    {{-- ocean texture + light-shaft --}}
    <div class="position-absolute top-0 start-0 w-100 h-100 dotted opacity-50"></div>
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:radial-gradient(60% 60% at 78% 8%, rgba(34,211,238,.35), transparent 60%)"></div>

    <main class="flex-grow-1 d-flex align-items-center position-relative py-5">
        <div class="container text-center" style="max-width:52rem">

            <p class="fs-2 fw-bold mb-4"><i class="bi bi-water text-info me-2"></i>Opti<span class="text-info">Tide</span></p>

            <span class="trust-pill eyebrow mb-4">🌊 Australian web design · SEO · hosting</span>

            <h1 class="display-3 fw-bold mb-3" style="line-height:1.04">
                Something better is <span class="text-gradient-ocean">on the way</span>.
            </h1>
            <p class="fs-5 text-white-50 mx-auto mb-5" style="max-width:34rem">
                We're building a brand-new home for OptiTide — bespoke websites, SEO that ranks, and rock-solid hosting. Launching very soon.
            </p>

            {{-- Countdown --}}
            {{-- TODO: set your real launch date/time (Australia/Sydney) below --}}
            <div id="cd" class="row g-2 g-md-3 justify-content-center mb-5" data-launch="2026-09-01T09:00:00+10:00">
                @foreach (['d' => 'Days', 'h' => 'Hours', 'm' => 'Minutes', 's' => 'Seconds'] as $key => $label)
                    <div class="col-3" style="max-width:8rem">
                        <div class="glass rounded-4 py-3">
                            <div id="cd-{{ $key }}" class="stat-num">--</div>
                            <div class="small text-white-50 text-uppercase" style="letter-spacing:.1em">{{ $label }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Lead capture — reuses the real free SEO audit flow --}}
            <form action="{{ route('seo-audit.show') }}" method="GET" class="mx-auto mb-3" style="max-width:32rem">
                <div class="input-group input-group-lg shadow-lg rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-globe text-secondary"></i></span>
                    <input type="url" name="url" class="form-control border-0" placeholder="yourbusiness.com.au" aria-label="Your website URL">
                    <button class="btn btn-accent fw-semibold px-4" type="submit">Free SEO audit</button>
                </div>
            </form>
            <p class="small text-white-50 mb-5"><i class="bi bi-stars text-warning me-1"></i>Get a head start — grab a free SEO audit while we build. No cost, no obligation.</p>

            {{-- Social + client login --}}
            {{-- TODO: point these at your real profiles --}}
            <div class="d-flex justify-content-center gap-3 fs-4 mb-4">
                <a href="#" class="link-light" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="link-light" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" class="link-light" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="#" class="link-light" aria-label="X"><i class="bi bi-twitter-x"></i></a>
            </div>
            <a href="/client/login" class="link-light small text-decoration-none"><i class="bi bi-box-arrow-in-right me-1"></i>Existing client? Log in</a>
        </div>
    </main>

    <footer class="position-relative text-center text-white-50 small py-4">
        <div class="container">
            <span class="me-3"><i class="bi bi-geo-alt-fill text-info me-1"></i>Australian-owned</span>
            &copy; {{ now()->year }} OptiTide. All rights reserved.
        </div>
    </footer>

    <script>
        (function () {
            var root = document.getElementById('cd');
            if (!root) return;
            var target = new Date(root.dataset.launch).getTime();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var set = function (id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
            function tick() {
                var diff = target - Date.now();
                if (isNaN(target) || diff <= 0) { root.style.display = 'none'; return; }
                set('cd-d', Math.floor(diff / 86400000));
                set('cd-h', pad(Math.floor(diff / 3600000) % 24));
                set('cd-m', pad(Math.floor(diff / 60000) % 60));
                set('cd-s', pad(Math.floor(diff / 1000) % 60));
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
</body>
</html>
