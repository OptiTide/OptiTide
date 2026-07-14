<x-site-layout title="Review your design — OptiTide">
    <section class="section">
        <div class="container" style="max-width:64rem">
            <p class="eyebrow text-primary">{{ $order->order_number }}</p>
            <h1 class="fw-bold display-6 mt-2">Review your design</h1>
            <p class="text-secondary" style="max-width:42rem">
                Here's the first design of your website. Click anywhere on it to leave a comment, then approve it to move
                into development — or request changes and we'll send a revised version.
            </p>

            {{-- Preview with click-to-annotate overlay --}}
            <div class="position-relative mt-4 overflow-hidden rounded-4 border shadow-sm" id="proof-frame">
                {{-- Untrusted AI HTML, fully isolated: sandboxed (no allow-same-origin)
                     and served from a CSP-protected route (connect-src 'none'). --}}
                <iframe
                    title="Design preview"
                    sandbox="allow-scripts"
                    src="{{ route('proofing.preview', $order) }}"
                    class="w-100 bg-white border-0 d-block"
                    style="height:75vh"
                ></iframe>
                {{-- Transparent overlay captures pin clicks (the iframe can't be clicked through). --}}
                <div id="proof-overlay" class="position-absolute top-0 start-0 w-100 h-100" style="cursor:crosshair" title="Click to leave a comment"></div>

                @foreach ($annotations as $i => $pin)
                    <div class="position-absolute translate-middle" style="left: {{ $pin->x }}%; top: {{ $pin->y }}%">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold border border-2 border-white shadow" style="width:1.5rem;height:1.5rem;font-size:.7rem" title="{{ $pin->comment }}">{{ $i + 1 }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Existing comments --}}
            @if ($annotations->isNotEmpty())
                <div class="mt-4">
                    <p class="fw-semibold">Your comments</p>
                    <ol class="list-unstyled d-grid gap-2 mt-3">
                        @foreach ($annotations as $i => $pin)
                            <li class="d-flex gap-3 text-secondary small">
                                <span class="d-flex flex-shrink-0 align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold" style="width:1.25rem;height:1.25rem;font-size:.6rem">{{ $i + 1 }}</span>
                                {{ $pin->comment }}
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            {{-- Decision --}}
            <div class="d-flex flex-column flex-sm-row gap-3 border-top mt-5 pt-4">
                <form method="POST" action="{{ route('proofing.approve', $order) }}" class="flex-fill">
                    @csrf
                    <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Approve this design</button>
                </form>
                <form method="POST" action="{{ route('proofing.changes', $order) }}" class="flex-fill"
                      onsubmit="return confirm('Request a revised design? Add any comments first so we know what to change.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-lg w-100">Request changes</button>
                </form>
            </div>
        </div>
    </section>

    {{-- Annotation form (hidden; shown when a pin is dropped) --}}
    <form method="POST" action="{{ route('proofing.annotate', $order) }}" id="annotation-form" class="d-none">
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
</x-site-layout>
