<x-filament-panels::page>
    <div
        wire:poll.10s="markSeen"
        class="mx-auto flex h-[70vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
    >
        <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10" wire:ignore>
            <span id="presence-dot" class="h-2 w-2 rounded-full bg-gray-300"></span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Support</span>
            <span id="presence-status" class="text-xs text-gray-400">— we usually reply within a few minutes</span>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto p-4" id="chat-scroll" wire:key="thread-{{ $this->conversationId }}">
            @forelse ($this->messages as $message)
                @php($role = $message->displayRoleFor(auth()->user()))
                <div class="flex {{ $role === 'you' ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $message->id }}">
                    <div @class([
                        'max-w-[75%] rounded-2xl px-4 py-2 text-sm',
                        'bg-primary-600 text-white' => $role === 'you',
                        'bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-100' => $role !== 'you',
                    ])>
                        @if ($role !== 'you')
                            <p class="mb-0.5 text-xs font-semibold opacity-70">Support</p>
                        @endif
                        {{ $message->body }}
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-gray-400">
                    Start the conversation — our team is here to help.
                </p>
            @endforelse

            {{-- Transient "agent is typing" bubble, filled live by streamed deltas.
                 wire:ignore so the poll morph doesn't wipe it mid-stream. --}}
            <div id="ai-typing" class="hidden justify-start" wire:ignore>
                <div class="max-w-[75%] rounded-2xl bg-gray-100 px-4 py-2 text-sm text-gray-800 dark:bg-white/10 dark:text-gray-100">
                    <p class="mb-0.5 text-xs font-semibold opacity-70">Support</p>
                    <span id="ai-typing-text"></span>
                </div>
            </div>
        </div>

        <form wire:submit="send" class="flex gap-2 border-t border-gray-200 p-3 dark:border-white/10">
            <input
                type="text"
                wire:model="body"
                placeholder="Type a message…"
                autocomplete="off"
                class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:border-white/10"
            />
            <x-filament::button type="submit" icon="heroicon-m-paper-airplane">Send</x-filament::button>
        </form>
    </div>

    @if (file_exists(public_path('build/manifest.json')))
        @vite('resources/js/app.js')
    @endif

    @script
    <script>
        const id = $wire.conversationId;

        // Progressive enhancement: instant updates when Reverb is connected.
        // Without it, wire:poll keeps the thread current.
        if (window.Echo && id) {
            window.Echo.private(`chat.conversation.${id}`)
                .listen('.message.sent', () => {
                    const box = document.getElementById('ai-typing');
                    const txt = document.getElementById('ai-typing-text');
                    box?.classList.add('hidden');
                    if (txt) txt.textContent = '';
                    $wire.markSeen();
                })
                .listen('.agent.typing', (e) => {
                    const box = document.getElementById('ai-typing');
                    const txt = document.getElementById('ai-typing-text');
                    if (box && txt) {
                        box.classList.remove('hidden');
                        txt.textContent += e.delta ?? '';
                    }
                });

            // Presence: light the dot when a support member is online.
            let members = [];
            const paint = () => {
                const online = members.some((m) => m.staff);
                const dot = document.getElementById('presence-dot');
                const status = document.getElementById('presence-status');
                if (dot) dot.className = 'h-2 w-2 rounded-full ' + (online ? 'bg-green-500' : 'bg-gray-300');
                if (status) status.textContent = online ? '— online now' : '— we usually reply within a few minutes';
            };
            window.Echo.join(`online.conversation.${id}`)
                .here((m) => { members = m; paint(); })
                .joining((m) => { members.push(m); paint(); })
                .leaving((m) => { members = members.filter((x) => x.id !== m.id); paint(); });
        }
    </script>
    @endscript
</x-filament-panels::page>
