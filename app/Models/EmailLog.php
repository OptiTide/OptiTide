<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * One row per email the system attempted to send. Written by
 * App\Services\Mail\LoggingMailer.
 */
class EmailLog extends Model
{
    protected static string $table = 'email_logs';

    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    /**
     * Strip single-use credentials out of an email body before it is stored.
     *
     * The password-reset and email-verification links are account-takeover URLs:
     * anyone who can read a stored copy can seize the account it was sent to,
     * without ever touching the recipient's mailbox. A reset token is usually
     * still live when the log row is written, so this is not theoretical.
     *
     * Deliberately keeps the surrounding link visible ("/reset-password/[redacted]")
     * so the log still answers "did we send them a reset link, and when" — which
     * is the actual support question — while being useless to an attacker.
     *
     * Pay and quote tokens are intentionally NOT redacted. They expose an invoice
     * to whoever holds them, but that is data staff can already open in the admin,
     * and support's most common job is re-sending exactly that link.
     */
    public static function redact(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // Path-segment tokens: /reset-password/<token>, /email/verify/<token>.
        $html = preg_replace(
            '~(/(?:reset-password|email/verify)/)[A-Za-z0-9._\-]+~i',
            '$1[redacted]',
            $html
        ) ?? $html;

        // Query-string credentials, whatever the surrounding URL.
        $html = preg_replace(
            '~\b(token|reset_token|verify_token|api_key|apikey|signature|secret|password)=[^"\'&\s<>]+~i',
            '$1=[redacted]',
            $html
        ) ?? $html;

        return $html;
    }

    /** Most recent first, optionally filtered. Returns [rows, total]. */
    public static function page(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $bind = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(to_email LIKE ? OR subject LIKE ?)';
            $bind[] = '%' . $search . '%';
            $bind[] = '%' . $search . '%';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'status = ?';
            $bind[] = $status;
        }

        $sql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
        $db = Database::instance();

        $total = (int) ($db->selectOne('SELECT COUNT(*) AS n FROM email_logs' . $sql, $bind)['n'] ?? 0);

        // Bind the paging values rather than interpolating them, even though they
        // are cast ints — the habit is what keeps the next edit safe.
        $rows = $db->select(
            'SELECT * FROM email_logs' . $sql . ' ORDER BY id DESC LIMIT ? OFFSET ?',
            array_merge($bind, [$limit, $offset])
        );

        return [$rows, $total];
    }

    /** Counts by status, for the summary strip. */
    public static function statusCounts(): array
    {
        $out = [self::STATUS_SENT => 0, self::STATUS_FAILED => 0, self::STATUS_SENDING => 0];

        foreach (Database::instance()->select('SELECT status, COUNT(*) AS n FROM email_logs GROUP BY status') as $row) {
            $out[(string) $row['status']] = (int) $row['n'];
        }

        return $out;
    }

    /**
     * Drop bodies older than $days, then delete rows older than $keepDays.
     *
     * Two stages on purpose. The body is the bulk of the row and the part that
     * holds personal data, so it should go early; the metadata (who, what,
     * when, did it fail) is small and is what you want months later when a
     * client says "you never invoiced me". Returns [bodiesCleared, rowsDeleted].
     */
    public static function prune(int $days = 90, int $keepDays = 730): array
    {
        $db = Database::instance();

        $bodyCutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $rowCutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));

        $bodies = $db->affecting(
            "UPDATE email_logs SET body_html = NULL WHERE body_html IS NOT NULL AND created_at < ?",
            [$bodyCutoff]
        );

        $rows = $db->affecting('DELETE FROM email_logs WHERE created_at < ?', [$rowCutoff]);

        return [$bodies, $rows];
    }
}
