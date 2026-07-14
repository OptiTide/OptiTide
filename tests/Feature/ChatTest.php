<?php

use App\Enums\ChatConversationStatus;
use App\Events\AiReplyChunk;
use App\Events\ChatMessageSent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

function chat(): ChatService
{
    return app(ChatService::class);
}

// ---------------------------------------------------------------------------
// Channel authorization
// ---------------------------------------------------------------------------

function authChannel($test, User $user, ChatConversation $conversation)
{
    // Verifying channel auth needs a signing driver; the default test driver is
    // `null` (no verification). Switch to reverb and re-load channels.php so the
    // channel closures register on the reverb broadcaster, not the null one.
    config(['broadcasting.default' => 'reverb']); // signs locally (HMAC), no server
    require base_path('routes/channels.php');

    $prefixes = ['private-chat.conversation.', 'presence-online.conversation.'];

    return collect($prefixes)->map(fn ($p) => $test->actingAs($user)->post('/broadcasting/auth', [
        'channel_name' => $p.$conversation->id,
        'socket_id' => '1234.5678',
    ]));
}

test('the conversation access rule allows the client and staff but no one else', function () {
    $client = User::factory()->create(['role' => 'client']);
    $other = User::factory()->create(['role' => 'client']);
    $staff = User::factory()->create(['role' => 'va']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);

    expect($conversation->canBeAccessedBy($client))->toBeTrue()
        ->and($conversation->canBeAccessedBy($staff))->toBeTrue()
        ->and($conversation->canBeAccessedBy($other))->toBeFalse();
});

test('the owning client can authorize on their conversation channels', function () {
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);

    authChannel($this, $client, $conversation)->each(fn ($r) => $r->assertOk());
});

test('a different client cannot authorize on the conversation channels', function () {
    $conversation = ChatConversation::factory()->create();
    $intruder = User::factory()->create(['role' => 'client']);

    authChannel($this, $intruder, $conversation)->each(fn ($r) => $r->assertForbidden());
});

test('any staff member can authorize on a conversation channel', function () {
    $conversation = ChatConversation::factory()->create();
    $staff = User::factory()->create(['role' => 'va']);

    authChannel($this, $staff, $conversation)->each(fn ($r) => $r->assertOk());
});

// ---------------------------------------------------------------------------
// Posting messages, broadcasting, and the AI fallback
// ---------------------------------------------------------------------------

test('a client message on an unassigned conversation broadcasts and triggers a streamed AI reply', function () {
    Event::fake([ChatMessageSent::class, AiReplyChunk::class]);
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);

    chat()->postMessage($conversation, ChatMessage::SENDER_CLIENT, $client, 'Hi, can you help?');

    // The AI reply was persisted as an `ai` message (fallback ran synchronously).
    $ai = ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->first();
    expect($ai)->not->toBeNull()
        ->and($ai->chat_conversation_id)->toBe($conversation->id);

    // Nudge broadcast for both the client message and the AI reply.
    Event::assertDispatched(ChatMessageSent::class, 2);
    // Live token deltas streamed (one per word from the fake).
    Event::assertDispatched(AiReplyChunk::class);
    expect($this->fake->streamedDeltas)->not->toBeEmpty();
});

test('an assigned conversation does not trigger the AI fallback', function () {
    Event::fake();
    $staff = User::factory()->create(['role' => 'va']);
    $conversation = ChatConversation::factory()->assignedTo($staff)->create();

    chat()->postMessage($conversation, ChatMessage::SENDER_CLIENT, $conversation->user, 'Hello?');

    expect(ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->count())->toBe(0);
    Event::assertDispatched(ChatMessageSent::class, 1); // only the client's message
});

test('claiming a conversation before the job runs silences the AI', function () {
    $staff = User::factory()->create(['role' => 'va']);
    $conversation = ChatConversation::factory()->create();
    $trigger = ChatMessage::factory()->fromClient()->create(['chat_conversation_id' => $conversation->id]);

    // Staff claimed it after the client message but before the job executes.
    chat()->assignToStaff($conversation, $staff);
    (new \App\Jobs\StreamAiReplyJob($conversation->id, $trigger->id))
        ->handle($this->fake, app(\App\Services\AI\ChatPromptBuilder::class), chat());

    expect(ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->count())->toBe(0);
});

test('an AI generation failure still posts a holding reply', function () {
    Event::fake();
    $this->fake->shouldThrow = true;
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);

    chat()->postMessage($conversation, ChatMessage::SENDER_CLIENT, $client, 'Are you a bot?');

    $ai = ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->first();
    expect($ai)->not->toBeNull()
        ->and($ai->body)->toContain('specialist');
});

// ---------------------------------------------------------------------------
// Stealth masking + read tracking
// ---------------------------------------------------------------------------

test('an AI reply is masked as an agent to the client but shown as ai to staff', function () {
    $client = User::factory()->create(['role' => 'client']);
    $staff = User::factory()->create(['role' => 'admin']);
    $ai = ChatMessage::factory()->fromAi()->create();
    $clientMsg = ChatMessage::factory()->fromClient()->create();

    expect($ai->displayRoleFor($client))->toBe('agent')   // never "ai"
        ->and($ai->displayRoleFor($staff))->toBe('ai')
        ->and($clientMsg->displayRoleFor($client))->toBe('you')
        ->and($clientMsg->displayRoleFor($staff))->toBe('client');
});

test('unread counts are side-aware and markRead clears the right ones', function () {
    $client = User::factory()->create(['role' => 'client']);
    $staff = User::factory()->create(['role' => 'va']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);
    ChatMessage::factory()->fromClient()->count(2)->create(['chat_conversation_id' => $conversation->id]);
    ChatMessage::factory()->fromStaff()->count(1)->create(['chat_conversation_id' => $conversation->id]);
    ChatMessage::factory()->fromAi()->count(1)->create(['chat_conversation_id' => $conversation->id]);

    // Staff sees the 2 client messages; client sees the staff + ai messages.
    expect($conversation->unreadCountFor($staff))->toBe(2)
        ->and($conversation->unreadCountFor($client))->toBe(2);

    chat()->markRead($conversation, $client);

    expect($conversation->unreadCountFor($client))->toBe(0)
        ->and($conversation->unreadCountFor($staff))->toBe(2); // untouched
});

// ---------------------------------------------------------------------------
// Conversation lifecycle
// ---------------------------------------------------------------------------

test('openConversationFor reuses an open conversation and creates one when needed', function () {
    $client = User::factory()->create(['role' => 'client']);

    $first = chat()->openConversationFor($client);
    $again = chat()->openConversationFor($client);
    expect($again->id)->toBe($first->id);

    chat()->close($first);
    $fresh = chat()->openConversationFor($client);
    expect($fresh->id)->not->toBe($first->id)
        ->and($fresh->status)->toBe(ChatConversationStatus::Open);
});

test('a new message reopens a closed conversation', function () {
    Event::fake();
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->closed()->assignedTo(
        User::factory()->create(['role' => 'va'])
    )->create(['user_id' => $client->id]);

    chat()->postMessage($conversation, ChatMessage::SENDER_CLIENT, $client, 'One more thing');

    expect($conversation->fresh()->status)->toBe(ChatConversationStatus::Open);
});

// ---------------------------------------------------------------------------
// Filament chat pages
// ---------------------------------------------------------------------------

test('a client can open the support chat page and staff can open the live inbox', function () {
    $client = User::factory()->create(['role' => 'client']);
    $staff = User::factory()->create(['role' => 'va']);

    $this->actingAs($client)->get(route('filament.client.pages.chat'))->assertOk();
    $this->actingAs($staff)->get(route('filament.admin.pages.chat'))->assertOk();
});

test('a client cannot open the staff live-chat page', function () {
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->get(route('filament.admin.pages.chat'))->assertForbidden();
});

test('the client chat page posts a message and triggers the AI reply', function () {
    $client = User::factory()->create(['role' => 'client']);
    $this->actingAs($client);
    Filament\Facades\Filament::setCurrentPanel('client');

    Livewire\Livewire::test(\App\Filament\Client\Pages\Chat::class)
        ->set('body', 'Hello, I need help')
        ->call('send')
        ->assertSet('body', '');

    expect(ChatMessage::where('sender_type', ChatMessage::SENDER_CLIENT)->where('body', 'Hello, I need help')->exists())->toBeTrue()
        ->and(ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->exists())->toBeTrue(); // fallback ran (unassigned)
});

test('the staff chat page reply claims the conversation and posts as staff', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Pages\Chat::class)
        ->call('selectConversation', $conversation->id)
        ->set('body', 'Happy to help!')
        ->call('send')
        ->assertSet('body', '');

    $conversation->refresh();
    expect($conversation->assigned_to)->toBe($staff->id) // replying claims it
        ->and(ChatMessage::where('sender_type', ChatMessage::SENDER_STAFF)->where('body', 'Happy to help!')->exists())->toBeTrue();
});

test('replying never steals a conversation already owned by another staffer', function () {
    $owner = User::factory()->create(['role' => 'va']);
    $other = User::factory()->create(['role' => 'admin']);
    $conversation = ChatConversation::factory()->assignedTo($owner)->create();
    $this->actingAs($other);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Pages\Chat::class)
        ->call('selectConversation', $conversation->id)
        ->set('body', 'jumping in')
        ->call('send');

    expect($conversation->fresh()->assigned_to)->toBe($owner->id); // unchanged
});

// ---------------------------------------------------------------------------
// Review fixes: IDOR lock + AI-reply idempotency
// ---------------------------------------------------------------------------

test('the client chat page rejects tampering with the locked conversation id', function () {
    $a = User::factory()->create(['role' => 'client']);
    $victim = ChatConversation::factory()->create(); // someone else's thread
    $this->actingAs($a);
    Filament\Facades\Filament::setCurrentPanel('client');

    $component = Livewire\Livewire::test(\App\Filament\Client\Pages\Chat::class);

    // #[Locked] rejects a client-driven update to conversationId.
    expect(fn () => $component->set('conversationId', $victim->id))->toThrow(Exception::class);
});

test('re-delivering the AI reply job does not double-post the reply', function () {
    Event::fake();
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);
    $trigger = ChatMessage::factory()->fromClient()->create(['chat_conversation_id' => $conversation->id]);
    $builder = app(\App\Services\AI\ChatPromptBuilder::class);

    $job = new \App\Jobs\StreamAiReplyJob($conversation->id, $trigger->id);
    $job->handle($this->fake, $builder, chat()); // posts the AI reply
    $job->handle($this->fake, $builder, chat()); // re-delivery: a newer message exists → bail

    expect(ChatMessage::where('sender_type', ChatMessage::SENDER_AI)->count())->toBe(1);
});

test('the AI reply broadcasts deltas coalesced into words, not per token', function () {
    Event::fake([AiReplyChunk::class]);
    $this->fake->nextResponse = 'Sure thing friend'; // three words
    $client = User::factory()->create(['role' => 'client']);
    $conversation = ChatConversation::factory()->create(['user_id' => $client->id]);
    $trigger = ChatMessage::factory()->fromClient()->create(['chat_conversation_id' => $conversation->id]);

    (new \App\Jobs\StreamAiReplyJob($conversation->id, $trigger->id))
        ->handle($this->fake, app(\App\Services\AI\ChatPromptBuilder::class), chat());

    // Word-coalesced: far fewer chunk events than the fake's per-word deltas would be
    // if forwarded raw, and never more than the word count.
    Event::assertDispatchedTimes(AiReplyChunk::class, 3);
});
