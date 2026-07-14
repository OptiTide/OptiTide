<x-store-layout title="Order received — OptiTide">
    <section class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">Thank you — order received</h1>
        <p class="mt-3 text-slate-600">
            Your order <span class="font-semibold text-slate-900">{{ $order->order_number }}</span> is confirmed.
            @unless ($order->isPaid())
                Payment confirmation usually lands within a minute.
            @endunless
        </p>

        <div class="mx-auto mt-10 max-w-md rounded-2xl bg-slate-50 p-6 text-left text-sm leading-6 text-slate-600">
            <p class="font-semibold text-slate-900">What happens next</p>
            <ol class="mt-3 list-decimal space-y-1.5 pl-5">
                <li>Your project onboarding form is waiting in the client portal.</li>
                <li>Tell us about your brand — logo, colors, goals.</li>
                <li>Our team gets to work and presents your design for review.</li>
            </ol>
        </div>

        <a href="/client" class="mt-10 inline-block rounded-xl bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
            Open your client portal
        </a>
    </section>
</x-store-layout>
