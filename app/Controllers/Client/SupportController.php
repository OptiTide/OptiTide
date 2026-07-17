<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Ticket;
use App\Services\Support\TicketService;

class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.support.index', [
            'title'   => 'Support',
            'tickets' => $clientId ? Ticket::forClient($clientId) : [],
        ]);
    }

    public function create(Request $request): Response
    {
        // Prefill lets other portal pages ("Ask for a quote", "Need a change?")
        // hand over a half-written request. The category is allow-listed against
        // the real list so a crafted URL can't inject an option; store() then
        // validates both like any other input.
        $category = (string) $request->query('category', '');

        return $this->view('client.support.create', [
            'title'    => 'New Support Request',
            'subject'  => (string) $request->query('subject', ''),
            'category' => in_array($category, Ticket::CATEGORIES, true) ? $category : '',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'subject'  => 'required|max:200',
            'category' => 'nullable|max:60',
            'priority' => 'nullable|in:low,normal,high',
            'body'     => 'required|max:5000',
        ]);

        $ticket = (new TicketService())->open(
            Auth::clientId(),
            Auth::id(),
            $data['subject'],
            $data['category'] ?: null,
            $data['priority'] ?? 'normal',
            $data['body'],
        );

        Session::flash('success', 'Your request has been logged. Our team will be in touch shortly.');

        return $this->redirect(route('portal.support.show', ['id' => $ticket['id']]));
    }

    public function show(Request $request, string $id): Response
    {
        $ticket = $this->ownedTicket($id);

        return $this->view('client.support.show', [
            'title'   => 'Ticket ' . $ticket['number'],
            'ticket'  => $ticket,
            // Internal staff notes are never exposed to the client.
            'replies' => Ticket::replies($id, false),
        ]);
    }

    public function reply(Request $request, string $id): Response
    {
        $ticket = $this->ownedTicket($id);

        if ($ticket['status'] === Ticket::STATUS_CLOSED) {
            Session::flash('error', 'This ticket is closed. Please open a new request.');

            return $this->redirect(route('portal.support.show', ['id' => $id]));
        }

        $data = $this->validate($request, ['body' => 'required|max:5000']);
        (new TicketService())->reply($id, Auth::id(), $data['body'], false, false);

        return $this->redirect(route('portal.support.show', ['id' => $id]));
    }

    /** Scope every lookup to the signed-in client (IDOR guard). */
    protected function ownedTicket(string $id): array
    {
        $ticket = Ticket::findOrFail($id);

        if ((string) $ticket['client_id'] !== (string) Auth::clientId()) {
            $this->abort(404, 'Ticket not found.');
        }

        return $ticket;
    }
}
