<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Support\TicketService;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');

        $all = Ticket::query()->orderBy('last_reply_at', 'desc')->orderBy('id', 'desc')->get();

        $counts = ['' => count($all)];
        foreach (Ticket::STATUSES as $key => $label) {
            $counts[$key] = count(array_filter($all, fn ($t) => $t['status'] === $key));
        }

        $tickets = ($status !== '' && isset(Ticket::STATUSES[$status]))
            ? array_values(array_filter($all, fn ($t) => $t['status'] === $status))
            : $all;

        return $this->view('admin.tickets.index', [
            'title'        => 'Helpdesk',
            'tickets'      => $tickets,
            'status'       => $status,
            'counts'       => $counts,
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $ticket = Ticket::findOrFail($id);

        return $this->view('admin.tickets.show', [
            'title'   => $ticket['subject'],
            'ticket'  => $ticket,
            'client'  => $ticket['client_id'] ? Client::find($ticket['client_id']) : null,
            'replies' => Ticket::replies($id, true),
            'authors' => array_column(User::all(), 'name', 'id'),
        ]);
    }

    public function reply(Request $request, string $id): Response
    {
        Ticket::findOrFail($id);
        $data = $this->validate($request, ['body' => 'required|max:5000']);
        $internal = $request->boolean('is_internal');

        (new TicketService())->reply($id, Auth::id(), $data['body'], true, $internal);
        Session::flash('success', $internal ? 'Internal note added.' : 'Reply sent to the client.');

        return $this->redirect(route('admin.tickets.show', ['id' => $id]));
    }

    public function status(Request $request, string $id): Response
    {
        Ticket::findOrFail($id);
        (new TicketService())->setStatus($id, (string) $request->input('status'));
        Session::flash('success', 'Ticket status updated.');

        return $this->redirect(route('admin.tickets.show', ['id' => $id]));
    }
}
