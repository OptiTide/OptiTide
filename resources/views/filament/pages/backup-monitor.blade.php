<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Backup health --}}
        @if ($diskError)
            <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 text-sm text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                {{ $diskError }}
            </div>
        @else
            <div @class([
                'rounded-xl border p-4 flex items-center gap-3',
                'border-success-300 bg-success-50 text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-300' => $healthy,
                'border-warning-300 bg-warning-50 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300' => ! $healthy,
            ])>
                <x-filament::icon
                    :icon="$healthy ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle'"
                    class="h-6 w-6 shrink-0"
                />
                <div>
                    <p class="font-semibold">{{ $healthy ? 'Healthy' : 'Attention needed' }}</p>
                    <p class="text-sm">{{ $healthMessage }}</p>
                </div>
            </div>
        @endif

        {{-- WHM server status --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">WHM / cPanel server</h3>
            @if (! $whmConfigured)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Not configured. Set <code>WHM_HOST</code>, <code>WHM_USERNAME</code> and <code>WHM_API_TOKEN</code> to enable server management.
                </p>
            @elseif ($whmError)
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $whmError }}</p>
            @else
                <dl class="mt-2 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Load (1m)</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $whmStatus['load'][0] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Load (5m)</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $whmStatus['load'][1] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Load (15m)</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $whmStatus['load'][2] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">WHM version</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $whmStatus['version'] ?? '—' }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        {{-- Archive list --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2 font-medium">Archive</th>
                        <th class="px-4 py-2 font-medium">Size</th>
                        <th class="px-4 py-2 font-medium">Age</th>
                        <th class="px-4 py-2 font-medium">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($backups as $backup)
                        <tr class="text-gray-950 dark:text-white">
                            <td class="px-4 py-2 font-mono text-xs">{{ $backup['name'] }}</td>
                            <td class="px-4 py-2">{{ $backup['size_mb'] }} MB</td>
                            <td class="px-4 py-2">{{ $backup['age'] }}</td>
                            <td class="px-4 py-2">{{ $backup['last_modified'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                No backup archives found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
