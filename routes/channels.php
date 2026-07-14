<?php

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/** True when $user may access conversation $id (its client, or any staff). */
$canAccessConversation = function (User $user, string $id): bool {
    $conversation = ChatConversation::find((int) $id);

    return $conversation !== null && $conversation->canBeAccessedBy($user);
};

/**
 * Message stream for a single conversation. The owning client and any staff
 * member may subscribe; nobody else.
 */
Broadcast::channel('chat.conversation.{id}', function (User $user, string $id) use ($canAccessConversation) {
    return $canAccessConversation($user, $id);
});

/**
 * Presence channel for online/typing status within a conversation. Same access
 * rule; the member metadata (never revealing the AI) drives the "agent is
 * online" indicator on the client side.
 */
Broadcast::channel('online.conversation.{id}', function (User $user, string $id) use ($canAccessConversation) {
    if (! $canAccessConversation($user, $id)) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->isStaff() ? 'Support' : $user->name,
        'staff' => $user->isStaff(),
    ];
});
