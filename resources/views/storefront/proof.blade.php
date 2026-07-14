<x-store-layout title="Review your design — OptiTide">
    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-sky-600">{{ $order->order_number }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Review your design</h1>
        <p class="mt-3 max-w-2xl text-slate-600">
            Here's the first design of your website. Click anywhere on it to leave a comment, then approve it to move
            into development — or request changes and we'll send a revised version.
        </p>

        {{-- Preview with click-to-annotate overlay --}}
        <div class="relative mt-8 overflow-hidden rounded-2xl border border-slate-200 shadow-sm" id="proof-frame">
            {{-- Untrusted AI HTML, fully isolated: sandboxed (no allow-same-origin)
                 and served from a CSP-protected route (connect-src 'none'). --}}
            <iframe
                title="Design preview"
                sandbox="allow-scripts"
                src="{{ route('proofing.preview', $order) }}"
                class="h-[75vh] w-full bg-white"
            ></iframe>
            {{-- Transparent overlay captures pin clicks (the iframe can't be clicked through). --}}
            <div id="proof-overlay" class="absolute inset-0 cursor-crosshair" title="Click to leave a comment"></div>

            @foreach ($annotations as $i => $pin)
                <div class="absolute -translate-x-1/2 -translate-y-1/2"
                     style="left: {{ $pin->x }}%; top: {{ $pin->y }}%">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white shadow ring-2 ring-white" title="{{ $pin->comment }}">{{ $i + 1 }}</span>
                </div>
            @endforeach
        </div>

        {{-- Existing comments --}}
        @if ($annotations->isNotEmpty())
            <div class="mt-6">
                <p class="text-sm font-semibold text-slate-900">Your comments</p>
                <ol class="mt-3 space-y-2">
                    @foreach ($annotations as $i => $pin)
                        <li class="flex gap-3 text-sm text-slate-600">
                            <span class="flex h-5 w-5 flex-none items-center justify-center rounded-full bg-sky-600 text-[10px] font-bold text-white">{{ $i + 1 }}</span>
                            {{ $pin->comment }}
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Decision --}}
        <div class="mt-10 flex flex-col gap-3 border-t border-slate-100 pt-8 sm:flex-row">
            <form method="POST" action="{{ route('proofing.approve', $order) }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-500">
                    Approve this design
                </button>
            </form>
            <form method="POST" action="{{ route('proofing.changes', $order) }}" class="flex-1"
                  onsubmit="return confirm('Request a revised design? Add any comments first so we know what to change.');">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400">
                    Request changes
                </button>
            </form>
        </div>
    </section>

    {{-- Annotation form (hidden; shown when a pin is dropped) --}}
    <form method="POST" action="{{ route('proofing.annotate', $order) }}" id="annotation-form" class="hidden">
        @csrf
        <input type="hidden" name="x" id="annotation-x">
        <input type="hidden" name="y" id="annotation-y">
        <input type="hidden" name="comment" id="annotation-comment">
    </form>

    <script>
        document.getElementById('proof-overlay').addEventListener('click', (e) => {
            const rect = e.currentTarget.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            const comment = window.prompt('What would you like to change here?');
            if (!comment) return;
            document.getElementById('annotation-x').value = x.toFixed(2);
            document.getElementById('annotation-y').value = y.toFixed(2);
            document.getElementById('annotation-comment').value = comment;
            document.getElementById('annotation-form').submit();
        });
    </script>
</x-store-layout>
