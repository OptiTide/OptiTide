<x-filament-panels::page>
    <form wire:submit="save" class="max-w-xl space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Just a couple of details so we can tailor your projects and invoices. This only takes a moment.
        </p>

        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-950 dark:text-white">
                Company / business name <span class="text-danger-600">*</span>
            </label>
            <input id="company_name" type="text" wire:model="company_name" autocomplete="organization"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            @error('company_name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-950 dark:text-white">
                Contact phone <span class="text-gray-400">(optional)</span>
            </label>
            <input id="phone" type="tel" wire:model="phone" autocomplete="tel"
                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white" />
            @error('phone') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <x-filament::button type="submit">
            Finish setup
        </x-filament::button>
    </form>
</x-filament-panels::page>
