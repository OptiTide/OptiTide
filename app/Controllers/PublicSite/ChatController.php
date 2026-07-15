<?php

namespace App\Controllers\PublicSite;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ChatConversation;
use App\Services\Chat\ChatService;

/**
 * Public live-chat endpoints (JSON). The 48-char conversation token is the
 * capability, stored in a cookie. Visitors never learn whether a reply came from
 * the AI or a human — the API only ever exposes sender = visitor|agent.
 */
class ChatController extends Controller
{
    public function start(Request $request): Response
    {
        $conversation = (new ChatService())->start(
            Auth::clientId(),
            trim((string) $request->input('name', '')) ?: null,
            trim((string) $request->input('email', '')) ?: null,
        );

        if (! headers_sent()) {
            setcookie('ot_chat', (string) $conversation['token'], [
                'expires'  => time() + 7 * 86400,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return $this->json([
            'token'    => $conversation['token'],
            'messages' => $this->format(ChatConversation::messages($conversation['id'])),
        ]);
    }

    public function message(Request $request): Response
    {
        $conversation = $this->resolve($request);
        $body = trim((string) $request->input('body', ''));
        if ($body === '') {
            return $this->json(['ok' => false], 422);
        }
        if (mb_strlen($body) > 2000) {
            $body = mb_substr($body, 0, 2000);
        }

        (new ChatService())->postVisitorMessage($conversation, $body);

        return $this->json([
            'ok'       => true,
            'messages' => $this->format(ChatConversation::messages($conversation['id'])),
        ]);
    }

    public function poll(Request $request): Response
    {
        $conversation = $this->resolve($request);
        $after = (int) $request->query('after', 0);

        $new = array_values(array_filter(
            ChatConversation::messages($conversation['id']),
            fn ($m) => (int) $m['id'] > $after
        ));

        return $this->json(['messages' => $this->format($new)]);
    }

    protected function resolve(Request $request): array
    {
        $token = (string) ($request->input('token') ?? $request->query('token') ?? $request->cookie('ot_chat', ''));
        $conversation = ChatConversation::byToken($token);
        if (! $conversation) {
            $this->abort(404, 'Conversation not found.');
        }

        return $conversation;
    }

    /** Only expose visitor/agent + body — never the AI-vs-human flag. */
    protected function format(array $messages): array
    {
        return array_map(fn ($m) => [
            'id'     => (int) $m['id'],
            'sender' => $m['sender'],
            'body'   => $m['body'],
        ], $messages);
    }
}
