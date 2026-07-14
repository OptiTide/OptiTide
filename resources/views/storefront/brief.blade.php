<x-site-layout title="Project Brief — OptiTide">
    @php($inputClass = 'form-control')
    <section class="section">
        <div class="container" style="max-width:42rem">
            <p class="eyebrow text-primary">{{ $order->order_number }}</p>
            <h1 class="fw-bold display-6 mt-2">{{ $schema->name }}</h1>
            @if ($schema->description)
                <p class="text-secondary">{{ $schema->description }}</p>
            @endif
            <p class="text-secondary small">
                The more you tell us, the closer your first design will be. Fields marked <span class="text-danger">*</span> are required.
            </p>

            <form method="POST" action="{{ route('brief.store', $order) }}" enctype="multipart/form-data" class="mt-4 d-grid gap-4">
                @csrf

                @foreach ($schema->schema['fields'] as $field)
                    @php($name = $field['name'])
                    @php($required = $field['required'] ?? false)
                    <div>
                        <label for="{{ $name }}" class="form-label fw-semibold">
                            {{ $field['label'] }}@if ($required) <span class="text-danger">*</span>@endif
                        </label>

                        @switch($field['type'])
                            @case('textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @if($required) required @endif class="{{ $inputClass }}">{{ old($name) }}</textarea>
                                @break

                            @case('select')
                                <select id="{{ $name }}" name="{{ $name }}" @if($required) required @endif class="form-select">
                                    <option value="">Select…</option>
                                    @foreach ($field['options'] ?? [] as $option)
                                        <option value="{{ $option }}" @selected(old($name) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('color')
                                <input type="color" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, '#0284c7') }}" class="form-control form-control-color" style="width:5rem;height:2.75rem">
                                @break

                            @case('file')
                                <input type="file" id="{{ $name }}" name="{{ $name }}@if($field['multiple'] ?? false)[]@endif"
                                       @if($field['multiple'] ?? false) multiple @endif
                                       @if(!empty($field['accept'])) accept="{{ $field['accept'] }}" @endif
                                       @if($required) required @endif
                                       class="form-control">
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

                        @error($name)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <div class="border-top pt-4">
                    <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-send me-2"></i>Submit project brief</button>
                    <p class="text-center text-secondary small mt-3 mb-0">Your files are stored privately and only used for your project.</p>
                </div>
            </form>
        </div>
    </section>
</x-site-layout>
