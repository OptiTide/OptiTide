<x-store-layout :title="$contract->title.' — OptiTide'">
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $contract->title }}</h1>
        <p class="mt-3 text-slate-600">
            Please review the agreement below and sign to proceed. A certified PDF copy will be saved to your account.
        </p>

        {{-- Agreement preview --}}
        <article class="prose prose-slate mt-8 max-w-none rounded-2xl border border-slate-200 bg-white p-8 text-sm leading-6 text-slate-700">
            @include('contracts.body.'.$contract->template_key, ['model' => $contract])
        </article>

        {{-- Signature --}}
        <div class="mt-10 rounded-2xl border border-slate-200 p-8">
            <p class="text-sm font-semibold text-slate-900">Sign here</p>
            <p class="mt-1 text-xs text-slate-500">Draw your signature in the box below, then submit.</p>

            <form method="POST" action="{{ route('contracts.sign', $contract) }}" class="mt-4">
                @csrf
                <x-creagia-signature-pad
                    :width="600"
                    :height="200"
                    border-color="#cbd5e1"
                    pad-classes="rounded-lg bg-slate-50"
                    button-classes="mt-4 mr-2 inline-block rounded-lg px-4 py-2 text-sm font-semibold cursor-pointer"
                    clear-name="Clear"
                    submit-name="Sign &amp; accept agreement"
                    :disabled-without-signature="true"
                />
            </form>

            <p class="mt-4 text-xs leading-5 text-slate-400">
                By signing you agree to the terms above. Your IP address and a timestamp are recorded with the signature for legal purposes. This does not limit your rights under the Australian Consumer Law.
            </p>
        </div>
    </section>

    {{-- Wires up the canvas → hidden input. Loads after the DOM via its own DOMContentLoaded hook. --}}
    <script src="{{ asset('vendor/sign-pad/sign-pad.min.js') }}"></script>
    <style>
        .sign-pad-button-clear { background:#f1f5f9; color:#334155; }
        .sign-pad-button-submit { background:#0284c7; color:#fff; }
        .sign-pad-button-submit:disabled { opacity:.5; cursor:not-allowed; }
    </style>
</x-store-layout>
