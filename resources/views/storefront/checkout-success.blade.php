<x-site-layout title="Order received — OptiTide">
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6 text-center">
                    <div class="feature-ico bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-4 d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                    </div>

                    <p class="eyebrow text-primary">Order confirmed</p>
                    <h1 class="fw-bold display-5 text-dark">Thank you — order received</h1>
                    <p class="fs-5 text-secondary mt-3">
                        Your order <span class="fw-semibold text-dark">{{ $order->order_number }}</span> is confirmed.
                        @unless ($order->isPaid())
                            Payment confirmation usually lands within a minute.
                        @endunless
                    </p>

                    <div class="card border-0 shadow-sm rounded-4 text-start mt-5 mx-auto" style="max-width:30rem">
                        <div class="card-body p-4">
                            <p class="fw-semibold text-dark mb-3"><i class="bi bi-list-check text-primary me-2"></i>What happens next</p>
                            <div class="d-grid gap-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-1-circle-fill text-primary fs-5 me-2"></i>
                                    <span class="text-secondary">Your project onboarding form is waiting in the client portal.</span>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-2-circle-fill text-primary fs-5 me-2"></i>
                                    <span class="text-secondary">Tell us about your brand — logo, colors, goals.</span>
                                </div>
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-3-circle-fill text-primary fs-5 me-2"></i>
                                    <span class="text-secondary">Our team gets to work and presents your design for review.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <a href="/client" class="btn btn-accent btn-lg fw-semibold">
                            Open your client portal <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-site-layout>
