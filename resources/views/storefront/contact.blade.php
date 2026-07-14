<x-store-layout title="Contact — OptiTide">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-16 lg:grid-cols-2">
            <div>
                <h1 class="text-4xl font-bold tracking-tight text-slate-900">Let's talk about your project</h1>
                <p class="mt-4 max-w-md text-lg leading-8 text-slate-600">
                    Tell us where your business is heading and we'll map out the website, SEO, and hosting to get you there. We reply within one business day.
                </p>

                <dl class="mt-10 space-y-6 text-sm">
                    <div class="flex gap-4">
                        <dt class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-sky-50">
                            <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a8.25 8.25 0 0 0 8.25-8.25c0-4.556-8.25-11.25-8.25-11.25S3.75 8.194 3.75 12.75A8.25 8.25 0 0 0 12 21Z"/></svg>
                        </dt>
                        <dd class="text-slate-600"><span class="block font-semibold text-slate-900">Based in Australia</span>Working with clients worldwide, in your timezone.</dd>
                    </div>
                    <div class="flex gap-4">
                        <dt class="flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-sky-50">
                            <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </dt>
                        <dd class="text-slate-600"><span class="block font-semibold text-slate-900">Fast turnaround</span>Replies within one business day, projects scoped within a week.</dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('contact.store') }}" class="rounded-2xl border border-slate-200 p-8 shadow-sm">
                @csrf

                {{-- Honeypot: hidden from humans, catches naive bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="company_website">Company website</label>
                    <input type="text" id="company_website" name="company_website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-900">Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-900">Email <span class="text-rose-500">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-slate-900">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="company" class="block text-sm font-semibold text-slate-900">Company</label>
                        <input type="text" id="company" name="company" value="{{ old('company') }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        @error('company')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="website_url" class="block text-sm font-semibold text-slate-900">Current website</label>
                        <input type="url" id="website_url" name="website_url" value="{{ old('website_url') }}" placeholder="https://" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        @error('website_url')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm font-semibold text-slate-900">What do you need? <span class="text-rose-500">*</span></label>
                        <textarea id="message" name="message" rows="5" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="mt-6 w-full rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
                    Send enquiry
                </button>
            </form>
        </div>
    </section>
</x-store-layout>
