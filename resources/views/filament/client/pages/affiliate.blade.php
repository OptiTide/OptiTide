<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Referral link --}}
        <x-filament::section>
            <x-slot name="heading">Your referral link</x-slot>
            <x-slot name="description">
                Share this link. When someone signs up through it and places their first paid order,
                you earn a commission.
            </x-slot>

            <div x-data="{ copied: false }" class="flex flex-col gap-2 sm:flex-row">
                <input
                    type="text" readonly
                    value="{{ $this->referralUrl }}"
                    x-ref="link"
                    class="flex-1 rounded-lg border-gray-300 bg-gray-50 text-sm dark:border-white/10 dark:bg-gray-800 dark:text-white"
                />
                <x-filament::button
                    x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                    icon="heroicon-o-clipboard"
                >
                    <span x-show="! copied">Copy</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Earnings summary --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @php($totals = $this->totals)
            <x-filament::section>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Referrals</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $this->referralCount }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Pending</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ $totals['pending']->format() }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Credit balance</p>
                <p class="mt-1 text-2xl font-bold text-sky-600">{{ $totals['credited']->format() }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Paid out</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $totals['paid']->format() }}</p>
            </x-filament::section>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Approved commissions can be taken as <strong>account credit</strong> from your
            <a href="{{ \App\Filament\Client\Resources\Commissions\CommissionResource::getUrl() }}" class="text-primary-600 underline">Referral earnings</a>
            page, or left for a cash payout. You have {{ $totals['approved']->format() }} approved and awaiting your choice.
        </p>
    </div>
</x-filament-panels::page>
