<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Meeting;
use App\Services\Mail\Mail;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.meetings.index', [
            'title'    => 'Meetings',
            'meetings' => $clientId ? Meeting::forClient($clientId) : [],
        ]);
    }

    /** A client requests a meeting time; admin confirms it. */
    public function store(Request $request): Response
    {
        $clientId = Auth::clientId();
        if (! $clientId) {
            return $this->redirectRoute('portal.dashboard');
        }

        $data = $this->validate($request, [
            'title'      => 'required|max:160',
            'meeting_at' => 'required',
            'description' => 'nullable|max:1000',
        ]);

        $at = str_replace('T', ' ', trim($data['meeting_at']));
        if (strlen($at) === 16) {
            $at .= ':00';
        }

        Meeting::create([
            'client_id'   => $clientId,
            'created_by'  => Auth::id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'meeting_at'  => $at,
            'status'      => Meeting::STATUS_REQUESTED,
        ]);

        // Notify the team that a client requested a meeting.
        try {
            $client = Client::find($clientId);
            Mail::to(config('company.email'), config('company.legal_name'))
                ->subject('Meeting request from ' . ($client['business_name'] ?? 'a client'))
                ->view('emails.meeting-request', ['client' => $client, 'title' => $data['title'], 'meeting_at' => $at])
                ->send();
        } catch (\Throwable $e) {
            // ignore
        }

        $this->flash('success', 'Thanks! We\'ve received your meeting request and will confirm a time with you shortly.');

        return $this->redirectRoute('portal.meetings');
    }
}
