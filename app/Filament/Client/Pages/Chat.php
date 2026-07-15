<?php

namespace App\Filament\Client\Pages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Client-facing support chat. One rolling conversation per client. Messages
 * render through per-viewer masking, so an AI fallback reply appears as
 * "Support", never as a bot (stealth-AI rule).
 */
class Chat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Support chat';

    protected static string | \UnitEnum | null $navigationGroup = 'Support';

    protected static ?string $title = 'Support chat';

    protected string $view = 'filament.client.pages.chat';

    // Locked: never re-hydrated from the client payload, so it can't be
    // tampered to point at another client's conversation.
    #[Locked]
    public int $conversationId;

    public string $body = '';

    public function mount(): void
    {
        $conversation = app(ChatService::class)->openConversationFor(Auth::user());
        $this->conversationId = $conversation->id;
        app(ChatService::class)->markRead($conversation, Auth::user());
    }

    #[Computed]
    public function conversation(): ChatConversation
    {
        // Defence-in-depth: scope to the authenticated client's own threads so
        // even a bypassed lock can't reach another tenant's conversation.
        return Auth::user()->chatConversations()->findOrFail($this->conversationId);
    }

    #[Computed]
    public function messages(): Collection
    {
        return $this->conversation->messages()->orderBy('id')->get();
    }

    public function send(): void
    {
        $body = trim($this->body);

        if ($body === '') {
            return;
        }

        app(ChatService::class)->postMessage(
            $this->conversation,
            ChatMessage::SENDER_CLIENT,
            Auth::user(),
            $body,
        );

        $this->body = '';
        unset($this->messages); // recompute so the new message renders immediately
    }

    /** Poll fallback + Echo refresh target: reload the thread and mark it read. */
    public function markSeen(): void
    {
        // Only write when there's something to clear — the poll fires every 10s.
        if ($this->conversation->unreadCountFor(Auth::user()) > 0) {
            app(ChatService::class)->markRead($this->conversation, Auth::user());
        }

        unset($this->messages);
    }
}
