<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3" wire:poll.5s="markSeen">
        {{-- Inbox --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900 md:col-span-1">
            <div class="border-b border-gray-200 p-3 text-sm font-semibold dark:border-white/10">Conversations</div>
            <div class="max-h-[62vh] divide-y divide-gray-100 overflow-y-auto dark:divide-white/5">
                @forelse ($this->conversations as $conversation)
                    @php($unread = $conversation->unread_count)
                    <button
                        type="button"
                        wire:click="selectConversation({{ $conversation->id }})"
                        wire:key="conv-{{ $conversation->id }}"
                        @class([
                            'flex w-full items-center justify-between gap-2 p-3 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5',
                            'bg-primary-50 dark:bg-primary-500/10' => $this->conversationId === $conversation->id,
                        ])
                    >
                        <span class="min-w-0 truncate">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $conversation->user->name }}</span>
                            <span class="block text-xs text-gray-400">
                                {{ $conversation->last_message_at?->diffForHumans() ?? '—' }}
                                @unless ($conversation->assignee) · <span class="text-amber-600">AI</span> @endunless
                            </span>
                        </span>
                        @if ($unread > 0)
                            <span class="shrink-0 rounded-full bg-primary-600 px-2 py-0.5 text-xs text-white">{{ $unread }}</span>
                        @endif
                    </button>
                @empty
                    <p class="p-4 text-center text-sm text-gray-400">No conversations yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Thread --}}
        <div class="flex h-[70vh] flex-col rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900 md:col-span-2">
            @if ($this->conversation)
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 p-3 dark:border-white/10">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $this->conversation->user->name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $this->conversation->status->getLabel() }}
                            @if ($this->conversation->assignee)
                                · {{ $this->conversation->assignee->name }}
                            @else
                                · <span class="text-amber-600">Unassigned — AI answering</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-filament::button size="sm" color="gray" wire:click="assignToMe">Assign to me</x-filament::button>
                        <x-filament::button size="sm" color="danger" wire:click="closeConversation">Close</x-filament::button>
                    </div>
                </div>

                <div class="flex-1 space-y-3 overflow-y-auto p-4" wire:key="staff-thread-{{ $this->conversationId }}">
                    @foreach ($this->messages as $message)
                        @php($role = $message->displayRoleFor(auth()->user()))
                        <div class="flex {{ $role === 'client' ? 'justify-start' : 'justify-end' }}" wire:key="smsg-{{ $message->id }}">
                            <div @class([
                                'max-w-[75%] rounded-2xl px-4 py-2 text-sm',
                                'bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-100' => $role === 'client',
                                'bg-primary-600 text-white' => $role !== 'client',
                            ])>
                                @if ($role === 'ai')
                                    <p class="mb-0.5 text-xs font-semibold text-amber-200">AI draft</p>
                                @endif
                                {{ $message->body }}
                            </div>
                        </div>
                    @endforeach

                    {{-- wire:ignore so the 5s poll morph never wipes the live JS-managed stream --}}
                    <div id="ai-typing" class="hidden justify-end" wire:ignore>
                        <div class="max-w-[75%] rounded-2xl bg-primary-600/80 px-4 py-2 text-sm text-white">
                            <p class="mb-0.5 text-xs font-semibold text-amber-200">AI draft</p>
                            <span id="ai-typing-text"></span>
                        </div>
                    </div>
                </div>

                <form wire:submit="send" class="flex gap-2 border-t border-gray-200 p-3 dark:border-white/10">
                    <input
                        type="text"
                        wire:model="body"
                        placeholder="Reply to {{ $this->conversation->user->name }}…"
                        autocomplete="off"
                        class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                    />
                    <x-filament::button type="submit" icon="heroicon-m-paper-airplane">Send</x-filament::button>
                </form>
            @else
                <div class="flex flex-1 items-center justify-center p-8 text-center text-sm text-gray-400">
                    Select a conversation to view and reply.
                </div>
            @endif
        </div>
    </div>

    @if (file_exists(public_path('build/manifest.json')))
        @vite('resources/js/app.js')
    @endif

    @script
    <script>
        let subscribed = null;

        function subscribe(id) {
            if (! window.Echo || ! id || subscribed === id) return;
            if (subscribed) window.Echo.leave(`chat.conversation.${subscribed}`);
            subscribed = id;
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
        }

        subscribe($wire.conversationId);
        $wire.$watch('conversationId', (value) => subscribe(value));
    </script>
    @endscript
</x-filament-panels::page>
