<?php

namespace App\Services\Support;

use App\Core\Database;
use App\Models\Ticket;
use App\Models\TicketReply;

/**
 * The single write path for the helpdesk. Creating a ticket or a reply always
 * goes through here so status + last_reply_at never drift. Convention:
 *   open    = waiting on us (staff)
 *   pending = waiting on the client
 * A client message flips a ticket to open; a public staff message flips it to
 * pending; an internal note changes nothing a client can see.
 */
final class TicketService
{
    /** Open a new ticket with its first message. */
    public function open(int|string|null $clientId, int|string|null $userId, string $subject, ?string $category, string $priority, string $body): array
    {
        return Database::instance()->transaction(function () use ($clientId, $userId, $subject, $category, $priority, $body) {
            $ticket = Ticket::create([
                'number'        => 'TKT-PENDING-' . str_random(12),
                'client_id'     => $clientId,
                'user_id'       => $userId,
                'subject'       => $subject,
                'category'      => $category,
                'status'        => Ticket::STATUS_OPEN,
                'priority'      => in_array($priority, array_keys(Ticket::PRIORITIES), true) ? $priority : 'normal',
                'last_reply_at' => now(),
            ]);

            Ticket::updateById($ticket['id'], ['number' => 'TKT-' . str_pad((string) $ticket['id'], 6, '0', STR_PAD_LEFT)]);

            $this->addReply($ticket['id'], $userId, $body, false, false, false);

            return Ticket::find($ticket['id']);
        });
    }

    /** Post a reply. $isStaff drives the status transition; $isInternal hides it from the client. */
    public function reply(int|string $ticketId, int|string|null $userId, string $body, bool $isStaff, bool $isInternal = false): array
    {
        return Database::instance()->transaction(function () use ($ticketId, $userId, $body, $isStaff, $isInternal) {
            $reply = $this->addReply($ticketId, $userId, $body, $isStaff, $isInternal, true);

            if (! $isInternal) {
                // Public reply moves the ball to the other party.
                $status = $isStaff ? Ticket::STATUS_PENDING : Ticket::STATUS_OPEN;
                Ticket::updateById($ticketId, ['status' => $status]);
            }

            return $reply;
        });
    }

    public function setStatus(int|string $ticketId, string $status): void
    {
        if (isset(Ticket::STATUSES[$status])) {
            Ticket::updateById($ticketId, ['status' => $status]);
        }
    }

    /** Low-level insert; bumps last_reply_at for public replies. */
    protected function addReply(int|string $ticketId, int|string|null $userId, string $body, bool $isStaff, bool $isInternal, bool $bump): array
    {
        $reply = TicketReply::create([
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'body'        => $body,
            'is_staff'    => $isStaff ? 1 : 0,
            'is_internal' => $isInternal ? 1 : 0,
        ]);

        if ($bump && ! $isInternal) {
            Ticket::updateById($ticketId, ['last_reply_at' => now()]);
        }

        return $reply;
    }
}
