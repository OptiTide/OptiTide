<?php

namespace App\Models;

use App\Core\Model;

class Ticket extends Model
{
    protected static string $table = 'tickets';

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN    => 'Open',
        self::STATUS_PENDING => 'Awaiting Client',
        self::STATUS_CLOSED  => 'Closed',
    ];

    /** How the client sees each status (their point of view). */
    public const CLIENT_STATUSES = [
        self::STATUS_OPEN    => 'With our team',
        self::STATUS_PENDING => 'Awaiting your reply',
        self::STATUS_CLOSED  => 'Closed',
    ];

    public const PRIORITIES = [
        'low'    => 'Low',
        'normal' => 'Normal',
        'high'   => 'High',
    ];

    public const CATEGORIES = ['General', 'Billing', 'Web Design', 'SEO', 'Social Media', 'Web Hosting'];

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('last_reply_at', 'desc')->orderBy('id', 'desc')->get();
    }

    /** Replies for a ticket; internal notes optionally hidden (client view). */
    public static function replies(int|string $ticketId, bool $includeInternal = true): array
    {
        $q = TicketReply::query()->where('ticket_id', $ticketId);
        if (! $includeInternal) {
            $q->where('is_internal', 0);
        }

        return $q->orderBy('id', 'asc')->get();
    }
}
