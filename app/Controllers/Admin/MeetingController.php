<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Meeting;
use App\Services\Audit\AuditLog;
use App\Services\Mail\Mail;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.meetings.index', [
            'title'        => 'Meetings',
            'meetings'     => Meeting::all(),
            'clients'      => Client::query()->orderBy('business_name')->get(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'client_id'   => 'required|exists:clients,id',
            'title'       => 'required|max:160',
            'meeting_at'  => 'required',
            'location'    => 'nullable|max:300',
            'description' => 'nullable|max:1000',
        ]);

        $at = str_replace('T', ' ', trim($data['meeting_at']));
        if (strlen($at) === 16) {
            $at .= ':00';
        }

        $meeting = Meeting::create([
            'client_id'   => $data['client_id'],
            'created_by'  => Auth::id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'meeting_at'  => $at,
            'location'    => $data['location'] ?: null,
            'status'      => Meeting::STATUS_SCHEDULED,
        ]);

        $client = Client::find($data['client_id']);
        if ($client && ! empty($client['email'])) {
            try {
                Mail::to($client['email'], $client['business_name'])
                    ->subject('Meeting invitation: ' . $data['title'])
                    ->view('emails.meeting-invite', ['meeting' => $meeting, 'client' => $client])
                    ->send();
            } catch (\Throwable $e) {
                // never block on the invite mail
            }
        }

        Session::flash('success', 'Meeting scheduled and the client has been invited.');

        return $this->redirect(route('admin.meetings.index'));
    }

    /** Confirm a client-requested meeting: optionally reschedule, add a link, and invite. */
    public function confirm(Request $request, string $id): Response
    {
        $meeting = Meeting::findOrFail($id);

        $data = $this->validate($request, [
            'meeting_at' => 'nullable',
            'location'   => 'nullable|max:300',
        ]);

        $update = ['status' => Meeting::STATUS_SCHEDULED];

        if (! empty($data['meeting_at'])) {
            $at = str_replace('T', ' ', trim($data['meeting_at']));
            if (strlen($at) === 16) {
                $at .= ':00';
            }
            $update['meeting_at'] = $at;
        }
        if (! empty($data['location'])) {
            $update['location'] = $data['location'];
        }

        Meeting::updateById($id, $update);
        $meeting = Meeting::find($id);

        $client = Client::find($meeting['client_id']);
        if ($client && ! empty($client['email'])) {
            try {
                Mail::to($client['email'], $client['business_name'])
                    ->subject('Meeting confirmed: ' . $meeting['title'])
                    ->view('emails.meeting-invite', ['meeting' => $meeting, 'client' => $client])
                    ->send();
            } catch (\Throwable $e) {
                // never block on the invite mail
            }
        }

        AuditLog::record('meeting.confirmed', 'meeting', $id);
        Session::flash('success', 'Meeting confirmed and the client has been notified.');

        return $this->redirect(route('admin.meetings.index'));
    }

    public function cancel(Request $request, string $id): Response
    {
        Meeting::findOrFail($id);
        Meeting::updateById($id, ['status' => Meeting::STATUS_CANCELLED]);
        AuditLog::record('meeting.cancelled', 'meeting', $id);
        Session::flash('status', 'Meeting cancelled.');

        return $this->redirect(route('admin.meetings.index'));
    }
}
