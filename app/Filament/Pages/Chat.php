<?php

namespace App\Filament\Pages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Staff support inbox: every conversation on the left, the selected thread on
 * the right. Replying claims the conversation for the replying staffer, which
 * silences the AI fallback. Staff see the true message source (client/staff/ai).
 */
class Chat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Live chat';

    protected static ?string $title = 'Live chat';

    protected string $view = 'filament.pages.chat';

    public ?int $conversationId = null;

    public string $body = '';

    #[Computed]
    public function conversations(): Collection
    {
        // Staff always count the client's unread messages — one aggregate query
        // instead of a COUNT per row (the inbox re-renders every poll).
        return ChatConversation::with('user', 'assignee')
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->whereNull('read_at')
                ->where('sender_type', ChatMessage::SENDER_CLIENT)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function conversation(): ?ChatConversation
    {
        return $this->conversationId
            ? ChatConversation::with('user', 'assignee')->find($this->conversationId)
            : null;
    }

    #[Computed]
    public function messages(): Collection
    {
        return $this->conversation
            ? $this->conversation->messages()->orderBy('id')->get()
            : new Collection;
    }

    public function selectConversation(int $id): void
    {
        $this->conversationId = $id;

        if ($conversation = $this->conversation) {
            app(ChatService::class)->markRead($conversation, Auth::user());
        }

        unset($this->messages, $this->conversations);
    }

    public function send(): void
    {
        $conversation = $this->conversation;
        $body = trim($this->body);

        if (! $conversation || $body === '') {
            return;
        }

        $chat = app(ChatService::class);
        // Claim an UNassigned thread (silences the AI). Never steal a thread
        // another staffer already owns — use "Assign to me" for that.
        if ($conversation->assigned_to === null) {
            $chat->assignToStaff($conversation, Auth::user());
        }
        $chat->postMessage($conversation, ChatMessage::SENDER_STAFF, Auth::user(), $body);

        $this->body = '';
        unset($this->messages, $this->conversations, $this->conversation);
    }

    public function assignToMe(): void
    {
        if ($conversation = $this->conversation) {
            app(ChatService::class)->assignToStaff($conversation, Auth::user());
            unset($this->conversations, $this->conversation);
        }
    }

    public function closeConversation(): void
    {
        if ($conversation = $this->conversation) {
            app(ChatService::class)->close($conversation);
            unset($this->conversations, $this->conversation);
        }
    }

    /** Poll fallback + Echo refresh target for the open thread. */
    public function markSeen(): void
    {
        if (($conversation = $this->conversation) && $conversation->unreadCountFor(Auth::user()) > 0) {
            app(ChatService::class)->markRead($conversation, Auth::user());
        }

        unset($this->messages, $this->conversations);
    }
}
