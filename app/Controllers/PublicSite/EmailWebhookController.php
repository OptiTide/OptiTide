<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\Support\EmailToTicketService;

/**
 * Inbound-email webhook. An external inbound-parse service (or a Proton forward
 * routed through one) POSTs each email here; we open/append a helpdesk ticket.
 * CSRF-exempt by design (external callers) — gated instead by a shared secret,
 * and fail-closed when no secret is configured.
 */
class EmailWebhookController extends Controller
{
    public function inbound(Request $request): Response
    {
        $secret = trim((string) config('mailbox.webhook_secret', ''));
        $provided = (string) ($request->input('token') ?? $request->query('token') ?? $request->header('X-Webhook-Token') ?? '');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return $this->json(['ok' => false, 'error' => 'unauthorised'], 403);
        }

        // Accept the common inbound-parse field names (Mailgun / Postmark / generic).
        $from = (string) ($request->input('from') ?? $request->input('sender') ?? $request->input('From') ?? '');
        $subject = (string) ($request->input('subject') ?? $request->input('Subject') ?? '');
        $body = (string) ($request->input('text') ?? $request->input('body-plain') ?? $request->input('TextBody') ?? $request->input('stripped-text') ?? $request->input('plain') ?? '');

        [$email, $name] = EmailToTicketService::parseFrom($from);
        if ($email === '') {
            return $this->json(['ok' => false, 'error' => 'no sender'], 422);
        }

        $result = (new EmailToTicketService())->ingest($email, $name, $subject, $body);

        return $this->json(['ok' => true] + $result);
    }
}
