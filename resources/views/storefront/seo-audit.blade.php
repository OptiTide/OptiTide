<x-store-layout
    title="Free Instant SEO Audit — OptiTide"
    description="Get a free, AI-powered SEO audit of your website in minutes. Enter your URL and we'll email you a branded report with the highest-impact fixes."
>
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-sky-600">Free tool</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-900">Instant SEO Audit</h1>
            <p class="mx-auto mt-3 max-w-xl text-lg leading-7 text-slate-600">
                Enter your website and email address. We'll analyse your page and send you a branded
                report with your score and the highest-impact fixes — usually within a few minutes.
            </p>
        </div>

        <form method="POST" action="{{ route('seo-audit.store') }}" class="mx-auto mt-10 max-w-lg space-y-5">
            @csrf

            {{-- Honeypot — humans never see or fill this. --}}
            <div class="hidden" aria-hidden="true">
                <label>Company website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div>
                <label for="website_url" class="block text-sm font-medium text-slate-700">Your website URL</label>
                <input
                    type="url" id="website_url" name="website_url" value="{{ old('website_url') }}"
                    placeholder="https://yourbusiness.com.au" required
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                @error('website_url')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Where should we send the report?</label>
                <input
                    type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="you@yourbusiness.com.au" required
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                @error('email')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">
                Get my free SEO audit
            </button>

            <p class="text-center text-xs text-slate-400">
                No spam. We'll email your report and may follow up once — unsubscribe anytime.
            </p>
        </form>
    </section>
</x-store-layout>
