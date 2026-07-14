{{-- Renders untrusted AI-generated HTML inside a sandboxed srcdoc iframe.
     `sandbox` without allow-same-origin keeps the markup fully isolated from
     the app origin — no cookies, no DOM access, no top navigation. --}}
@php($artifact = $order->latestArtifact(\App\Enums\ArtifactType::MockupHtml))
<div class="space-y-3">
    @if ($artifact === null || blank($artifact->content))
        <p class="text-sm text-gray-500">No mockup has been generated yet.</p>
    @else
        <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Version {{ $artifact->version }} · {{ $artifact->status->getLabel() }}</span>
            <span>{{ $artifact->updated_at?->diffForHumans() }}</span>
        </div>
        {{-- Loaded from a CSP-protected route (not srcdoc) so connect-src 'none'
             blocks any outbound beaconing from attacker-steered script, while
             the sandbox keeps it isolated from the app origin. --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <iframe
                title="Mockup preview (version {{ $artifact->version }})"
                sandbox="allow-scripts"
                src="{{ route('proofing.preview', $order) }}"
                class="h-[70vh] w-full bg-white"
                loading="lazy"
            ></iframe>
        </div>
        @if ($artifact->status === \App\Enums\ArtifactStatus::Rejected && isset($artifact->prompt_context['error']))
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700">
                Generation failed: {{ $artifact->prompt_context['error'] }}
            </p>
        @endif
    @endif

    @isset($logicCode)
        <div>
            <p class="mb-1 text-xs font-semibold text-gray-500">Generated application logic (app.js)</p>
            <pre class="max-h-64 overflow-auto rounded-xl bg-gray-900 p-4 text-xs leading-relaxed text-gray-100"><code>{{ $logicCode }}</code></pre>
        </div>
    @endisset
</div>
