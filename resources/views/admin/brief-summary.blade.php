@php
    $schema = $submission?->formSchema;
    $fieldsByName = collect($schema?->schema['fields'] ?? [])->keyBy('name');
@endphp

<div class="space-y-4 text-sm">
    @if ($submission === null)
        <p class="text-gray-500">No brief has been submitted for this order yet.</p>
    @else
        <p class="text-xs text-gray-500">
            Submitted {{ $submission->submitted_at?->format('d M Y, g:ia') }}
        </p>

        {{-- Text answers --}}
        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($submission->data ?? [] as $name => $value)
                <div class="grid grid-cols-3 gap-3 py-2">
                    <dt class="font-medium text-gray-700 dark:text-gray-300">{{ $fieldsByName[$name]['label'] ?? $name }}</dt>
                    <dd class="col-span-2 whitespace-pre-line text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>

        {{-- Brand assets: colours as swatches, files as download links --}}
        @if (! empty($submission->brand_assets))
            <div>
                <p class="mb-2 font-semibold text-gray-700 dark:text-gray-300">Brand assets</p>
                <dl class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($submission->brand_assets as $name => $value)
                        <div class="grid grid-cols-3 gap-3 py-2">
                            <dt class="font-medium text-gray-700 dark:text-gray-300">{{ $fieldsByName[$name]['label'] ?? $name }}</dt>
                            <dd class="col-span-2 text-gray-900 dark:text-gray-100">
                                @php($type = $fieldsByName[$name]['type'] ?? null)
                                @if ($type === 'color')
                                    <span class="inline-flex items-center gap-2">
                                        <span class="inline-block h-5 w-5 rounded border border-gray-300" style="background-color: {{ $value }}"></span>
                                        <code>{{ $value }}</code>
                                    </span>
                                @elseif (is_array($value))
                                    @forelse ($value as $i => $path)
                                        <a href="{{ route('brief.asset', ['order' => $order, 'path' => $path]) }}" class="mr-3 text-primary-600 underline" target="_blank">{{ basename($path) }}</a>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                @elseif (filled($value))
                                    <a href="{{ route('brief.asset', ['order' => $order, 'path' => $value]) }}" class="text-primary-600 underline" target="_blank">{{ basename($value) }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    @endif
</div>
