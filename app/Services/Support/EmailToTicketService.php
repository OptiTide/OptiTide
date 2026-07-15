<?php

namespace App\Services\Support;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;

/**
 * Turns an inbound email into a helpdesk ticket — the shared path for both the
 * webhook and the IMAP importer. If the subject carries a TKT-###### reference
 * the message is appended to that ticket; otherwise a new ticket is opened and
 * matched to a client by sender email where possible.
 */
final class EmailToTicketService
{
    public function ingest(string $fromEmail, string $fromName, string $subject, string $body): array
    {
        $fromEmail = strtolower(trim($fromEmail));
        $subject = trim($subject) !== '' ? trim($subject) : '(no subject)';
        $body = trim($body) !== '' ? trim($body) : '(empty message)';
        $tickets = new TicketService();

        // Reply to an existing ticket if the subject references one.
        if (preg_match('/TKT-(\d{6})/i', $subject, $m)) {
            $ticket = Ticket::firstWhere('number', 'TKT-' . $m[1]);
            if ($ticket) {
                $tickets->reply($ticket['id'], $this->userIdFor($fromEmail), $body, false, false);

                return ['action' => 'reply', 'ticket' => $ticket['number']];
            }
        }

        $client = $fromEmail !== '' ? Client::firstWhere('email', $fromEmail) : null;
        $ticket = $tickets->open(
            $client['id'] ?? null,
            $this->userIdFor($fromEmail),
            $subject,
            'General',
            'normal',
            $body . "\n\n— received by email from " . ($fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail),
        );

        return ['action' => 'new', 'ticket' => $ticket['number']];
    }

    protected function userIdFor(string $email): ?int
    {
        if ($email === '') {
            return null;
        }
        $user = User::findByEmail($email);

        return $user ? (int) $user['id'] : null;
    }

    /** Extract [email, name] from a "Name <email>" or bare-email string. */
    public static function parseFrom(string $from): array
    {
        $from = trim($from);
        if (preg_match('/^(.*?)<([^>]+)>\s*$/', $from, $m)) {
            return [strtolower(trim($m[2])), trim($m[1], " \"'")];
        }

        return [strtolower($from), ''];
    }
}
