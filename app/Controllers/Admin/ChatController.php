<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ChatConversation;
use App\Services\Chat\ChatService;
use App\Support\Features;

class ChatController extends Controller
{
    /**
     * With live chat off the visitor's widget and poll endpoint are both gone, so
     * a reply typed here could never be delivered. Existing transcripts are not
     * deleted — switching the feature back on restores the inbox.
     */
    protected function guard(): void
    {
        if (! Features::enabled('live_chat')) {
            $this->abort(404, 'Live chat is switched off.');
        }
    }

    public function index(Request $request): Response
    {
        $this->guard();

        return $this->view('admin.chat.index', [
            'title'         => 'Live Chat',
            'conversations' => ChatConversation::queue(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $this->guard();
        $conversation = ChatConversation::findOrFail($id);

        return $this->view('admin.chat.show', [
            'title'        => 'Chat — ' . ($conversation['name'] ?: 'Visitor'),
            'conversation' => $conversation,
            'messages'     => ChatConversation::messages($id),
        ]);
    }

    public function reply(Request $request, string $id): Response
    {
        $this->guard();
        ChatConversation::findOrFail($id);
        $data = $this->validate($request, ['body' => 'required|max:2000']);

        $service = new ChatService();
        $service->takeOver($id);                              // human takes over — AI stops
        $service->postAgent($id, $data['body'], false, Auth::id());

        return $this->redirect(route('admin.chat.show', ['id' => $id]));
    }

    public function takeover(Request $request, string $id): Response
    {
        $this->guard();
        ChatConversation::findOrFail($id);
        (new ChatService())->takeOver($id);
        Session::flash('success', 'You\'ve taken over this chat — the assistant will stay quiet.');

        return $this->redirect(route('admin.chat.show', ['id' => $id]));
    }

    public function close(Request $request, string $id): Response
    {
        $this->guard();
        ChatConversation::findOrFail($id);
        (new ChatService())->setStatus($id, ChatConversation::STATUS_CLOSED);
        Session::flash('status', 'Chat closed.');

        return $this->redirect(route('admin.chat.index'));
    }
}
