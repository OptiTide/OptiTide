<x-site-layout :title="$contract->title.' — OptiTide'">
    <section class="section">
        <div class="container" style="max-width:48rem">
            <h1 class="fw-bold display-6">{{ $contract->title }}</h1>
            <p class="text-secondary">
                Please review the agreement below and sign to proceed. A certified PDF copy will be saved to your account.
            </p>

            {{-- Agreement preview --}}
            <article class="card border shadow-sm rounded-4 mt-4">
                <div class="card-body p-4 p-md-5 small" style="line-height:1.6">
                    @include('contracts.body.'.$contract->template_key, ['model' => $contract])
                </div>
            </article>

            {{-- Signature --}}
            <div class="card border shadow-sm rounded-4 mt-4">
                <div class="card-body p-4 p-md-5">
                    <p class="fw-semibold mb-1">Sign here</p>
                    <p class="text-secondary small">Draw your signature in the box below, then submit.</p>

                    <form method="POST" action="{{ route('contracts.sign', $contract) }}" class="mt-3">
                        @csrf
                        <x-creagia-signature-pad
                            :width="600"
                            :height="200"
                            border-color="#cbd5e1"
                            pad-classes="rounded-3 bg-light"
                            button-classes="mt-3 me-2 d-inline-block rounded-3 px-4 py-2 fw-semibold"
                            clear-name="Clear"
                            submit-name="Sign &amp; accept agreement"
                            :disabled-without-signature="true"
                        />
                    </form>

                    <p class="text-secondary small mt-4 mb-0">
                        By signing you agree to the terms above. Your IP address and a timestamp are recorded with the signature for legal purposes. This does not limit your rights under the Australian Consumer Law.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Wires up the canvas → hidden input. Loads after the DOM via its own DOMContentLoaded hook. --}}
    <script src="{{ asset('vendor/sign-pad/sign-pad.min.js') }}"></script>
    <style>
        .sign-pad-button-clear { background:#f1f5f9; color:#334155; cursor:pointer; }
        .sign-pad-button-submit { background:#0e7490; color:#fff; cursor:pointer; }
        .sign-pad-button-submit:disabled { opacity:.5; cursor:not-allowed; }
    </style>
</x-site-layout>
