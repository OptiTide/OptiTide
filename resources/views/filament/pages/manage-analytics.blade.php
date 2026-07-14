<x-filament-panels::page>
    <form wire:submit="save" class="max-w-xl space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Paste your tracking IDs below. We only store the ID and render the official snippet —
            never a custom script. Leave a field blank to disable that tracker.
        </p>

        <div>
            <label for="ga4" class="block text-sm font-medium text-gray-950 dark:text-white">Google Analytics 4 — Measurement ID</label>
            <input id="ga4" type="text" wire:model="ga4" placeholder="G-XXXXXXXXXX"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            @error('ga4') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="gtm" class="block text-sm font-medium text-gray-950 dark:text-white">Google Tag Manager — Container ID</label>
            <input id="gtm" type="text" wire:model="gtm" placeholder="GTM-XXXXXXX"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            @error('gtm') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="pixel" class="block text-sm font-medium text-gray-950 dark:text-white">Meta (Facebook) Pixel — ID</label>
            <input id="pixel" type="text" wire:model="pixel" placeholder="123456789012345"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            @error('pixel') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <x-filament::button type="submit">Save</x-filament::button>
    </form>
</x-filament-panels::page>
