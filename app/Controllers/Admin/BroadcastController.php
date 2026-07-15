<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Services\Mail\Mail;

/**
 * Mass email — broadcast an announcement to clients (all, or filtered by status).
 * Admin only. Sent synchronously with a per-recipient try/catch so one bad
 * address never aborts the run.
 */
class BroadcastController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can send mass email.');

        $counts = [
            'all'       => count(Client::query()->whereNotNull('email')->get()),
            'active'    => count(Client::query()->where('status', Client::STATUS_ACTIVE)->whereNotNull('email')->get()),
            'suspended' => count(Client::query()->where('status', Client::STATUS_SUSPENDED)->whereNotNull('email')->get()),
        ];

        return $this->view('admin.broadcast.index', [
            'title'  => 'Mass Email',
            'counts' => $counts,
        ]);
    }

    public function send(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can send mass email.');

        $data = $this->validate($request, [
            'audience'    => 'required|in:all,active,suspended',
            'subject'     => 'required|max:200',
            'body'        => 'required|max:8000',
            'cta_text'    => 'nullable|max:60',
            'cta_url'     => 'nullable|url|max:300',
        ]);

        $query = Client::query()->whereNotNull('email');
        if ($data['audience'] === 'active') {
            $query->where('status', Client::STATUS_ACTIVE);
        } elseif ($data['audience'] === 'suspended') {
            $query->where('status', Client::STATUS_SUSPENDED);
        }

        $sent = 0;
        $failed = 0;
        foreach ($query->get() as $client) {
            $email = trim((string) ($client['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            try {
                Mail::to($email, $client['business_name'] ?? null)
                    ->subject($data['subject'])
                    ->view('emails.broadcast', [
                        'client'  => $client,
                        'subject' => $data['subject'],
                        'body'    => $data['body'],
                        'ctaText' => trim((string) ($data['cta_text'] ?? '')),
                        'ctaUrl'  => trim((string) ($data['cta_url'] ?? '')),
                    ])
                    ->send();
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        Session::flash('success', "Broadcast sent to {$sent} client(s)" . ($failed ? " ({$failed} failed)" : '') . '.');

        return $this->redirect(route('admin.broadcast.index'));
    }
}
