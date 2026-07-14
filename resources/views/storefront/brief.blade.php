<x-store-layout title="Project Brief — OptiTide">
    @php($inputClass = 'mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200')
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-sky-600">{{ $order->order_number }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $schema->name }}</h1>
        @if ($schema->description)
            <p class="mt-3 text-slate-600">{{ $schema->description }}</p>
        @endif
        <p class="mt-2 text-sm text-slate-500">
            The more you tell us, the closer your first design will be. Fields marked <span class="text-rose-500">*</span> are required.
        </p>

        <form method="POST" action="{{ route('brief.store', $order) }}" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf

            @foreach ($schema->schema['fields'] as $field)
                @php($name = $field['name'])
                @php($required = $field['required'] ?? false)
                <div>
                    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-900">
                        {{ $field['label'] }}@if ($required) <span class="text-rose-500">*</span>@endif
                    </label>

                    @switch($field['type'])
                        @case('textarea')
                            <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @if($required) required @endif class="{{ $inputClass }}">{{ old($name) }}</textarea>
                            @break

                        @case('select')
                            <select id="{{ $name }}" name="{{ $name }}" @if($required) required @endif class="{{ $inputClass }}">
                                <option value="">Select…</option>
                                @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}" @selected(old($name) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('color')
                            <input type="color" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, '#0284c7') }}" class="mt-1.5 h-11 w-24 cursor-pointer rounded-lg border border-slate-300">
                            @break

                        @case('file')
                            <input type="file" id="{{ $name }}" name="{{ $name }}@if($field['multiple'] ?? false)[]@endif"
                                   @if($field['multiple'] ?? false) multiple @endif
                                   @if(!empty($field['accept'])) accept="{{ $field['accept'] }}" @endif
                                   @if($required) required @endif
                                   class="mt-1.5 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700 hover:file:bg-sky-100">
                            @break

                        @case('date')
                            <input type="date" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}" @if($required) required @endif class="{{ $inputClass }}">
                            @break

                        @case('url')
                            <input type="url" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}" placeholder="https://" @if($required) required @endif class="{{ $inputClass }}">
                            @break

                        @default
                            <input type="text" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}" @if($required) required @endif class="{{ $inputClass }}">
                    @endswitch

                    @error($name)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div class="border-t border-slate-100 pt-6">
                <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
                    Submit project brief
                </button>
                <p class="mt-3 text-center text-xs text-slate-400">Your files are stored privately and only used for your project.</p>
            </div>
        </form>
    </section>
</x-store-layout>
