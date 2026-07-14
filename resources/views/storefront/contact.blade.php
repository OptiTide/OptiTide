<x-site-layout title="Contact — OptiTide">
    <section class="section">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <p class="eyebrow text-primary">Get in touch</p>
                    <h1 class="fw-bold display-5 text-dark">Let's talk about your project</h1>
                    <p class="fs-5 text-secondary mt-3" style="max-width:30rem">
                        Tell us where your business is heading and we'll map out the website, SEO, and hosting to get you there. We reply within one business day.
                    </p>

                    <div class="d-grid gap-3 mt-4">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="feature-ico bg-light text-primary flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-3">
                                <i class="bi bi-geo-alt-fill fs-5"></i>
                            </span>
                            <div>
                                <span class="d-block fw-semibold text-dark">Based in Australia</span>
                                <span class="text-secondary">Working with clients worldwide, in your timezone.</span>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <span class="feature-ico bg-light text-primary flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-3">
                                <i class="bi bi-clock-fill fs-5"></i>
                            </span>
                            <div>
                                <span class="d-block fw-semibold text-dark">Fast turnaround</span>
                                <span class="text-secondary">Replies within one business day, projects scoped within a week.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('contact.store') }}">
                                @csrf

                                {{-- Honeypot: hidden from humans, catches naive bots. --}}
                                <div class="d-none" aria-hidden="true">
                                    <label for="company_website">Company website</label>
                                    <input type="text" id="company_website" name="company_website" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="name" class="form-label fw-semibold text-dark">Name <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="email" class="form-label fw-semibold text-dark">Email <span class="text-danger">*</span></label>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">
                                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="phone" class="form-label fw-semibold text-dark">Phone</label>
                                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="company" class="form-label fw-semibold text-dark">Company</label>
                                        <input type="text" id="company" name="company" value="{{ old('company') }}" class="form-control @error('company') is-invalid @enderror">
                                        @error('company')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="website_url" class="form-label fw-semibold text-dark">Current website</label>
                                        <input type="url" id="website_url" name="website_url" value="{{ old('website_url') }}" placeholder="https://" class="form-control @error('website_url') is-invalid @enderror">
                                        @error('website_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label fw-semibold text-dark">What do you need? <span class="text-danger">*</span></label>
                                        <textarea id="message" name="message" rows="5" required class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                        @error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-accent btn-lg fw-semibold">
                                        <i class="bi bi-send me-2"></i>Send enquiry
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-site-layout>
