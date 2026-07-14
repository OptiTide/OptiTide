<x-site-layout
    title="Free Instant SEO Audit — OptiTide"
    description="Get a free, AI-powered SEO audit of your website in minutes. Enter your URL and we'll email you a branded report with the highest-impact fixes."
>
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="text-center mb-5">
                        <p class="eyebrow text-primary">Free tool</p>
                        <h1 class="fw-bold display-5 text-dark">Instant SEO Audit</h1>
                        <p class="fs-5 text-secondary mt-3 mb-0">
                            Enter your website and email address. We'll analyse your page and send you a branded
                            report with your score and the highest-impact fixes — usually within a few minutes.
                        </p>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('seo-audit.store') }}">
                                @csrf

                                {{-- Honeypot — humans never see or fill this. --}}
                                <div class="d-none" aria-hidden="true">
                                    <label>Company website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                                </div>

                                <div class="mb-4">
                                    <label for="website_url" class="form-label fw-semibold">Your website URL</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light"><i class="bi bi-globe2 text-primary"></i></span>
                                        <input
                                            type="url" id="website_url" name="website_url" value="{{ old('website_url') }}"
                                            placeholder="https://yourbusiness.com.au" required
                                            class="form-control @error('website_url') is-invalid @enderror"
                                        >
                                        @error('website_url')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">Where should we send the report?</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light"><i class="bi bi-envelope text-primary"></i></span>
                                        <input
                                            type="email" id="email" name="email" value="{{ old('email') }}"
                                            placeholder="you@yourbusiness.com.au" required
                                            class="form-control @error('email') is-invalid @enderror"
                                        >
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-accent btn-lg fw-semibold">
                                        <i class="bi bi-search me-2"></i>Get my free SEO audit
                                    </button>
                                </div>

                                <p class="text-center small text-secondary mt-3 mb-0">
                                    No spam. We'll email your report and may follow up once — unsubscribe anytime.
                                </p>
                            </form>
                        </div>
                    </div>

                    <ul class="list-unstyled d-flex flex-wrap justify-content-center gap-3 gap-md-4 small text-secondary mt-4 mb-0">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Results in minutes</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Branded PDF report</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Highest-impact fixes first</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</x-site-layout>
