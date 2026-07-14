<?php

namespace App\Models;

use App\Enums\ChatConversationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'status',
        'last_message_at',
    ];

    // Fresh instances have a usable status before a DB round-trip.
    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChatConversationStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ChatConversationStatus::Open);
    }

    /** True when $user is the client who owns this thread (not staff). */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /** Who may read/subscribe to this conversation: its client, or any staff. */
    public function canBeAccessedBy(User $user): bool
    {
        return $user->isStaff() || $this->isOwnedBy($user);
    }

    /**
     * Unread messages for $user: a client counts staff/ai replies; a staff
     * member counts the client's messages. A user never has unread counts for
     * their own side's messages.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->whereIn('sender_type', $this->incomingSenderTypesFor($user))
            ->count();
    }

    /** Mark the messages addressed to $user as read. */
    public function markReadBy(User $user): void
    {
        $this->messages()
            ->whereNull('read_at')
            ->whereIn('sender_type', $this->incomingSenderTypesFor($user))
            ->update(['read_at' => now()]);
    }

    /** @return array<int, string> sender types that count as "incoming" for $user */
    protected function incomingSenderTypesFor(User $user): array
    {
        return $user->isStaff()
            ? [ChatMessage::SENDER_CLIENT]
            : [ChatMessage::SENDER_STAFF, ChatMessage::SENDER_AI];
    }
}
