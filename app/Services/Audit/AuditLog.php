<?php

namespace App\Services\Audit;

use App\Core\Auth;
use App\Models\AuditEntry;

/**
 * Append-only audit trail. Every meaningful action (sign-in, money movement,
 * status change, admin override) should leave one row here. Recording must
 * NEVER throw into the caller — a logging failure can't be allowed to break the
 * business action it's describing.
 */
final class AuditLog
{
    /**
     * @param  string       $action       dotted verb, e.g. "invoice.sent", "user.login"
     * @param  string|null  $subjectType  the entity kind, e.g. "invoice", "order", "user"
     * @param  array        $meta         extra JSON context (amounts, from/to, etc.)
     */
    public static function record(
        string $action,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $meta = [],
        ?array $actor = null,
    ): void {
        try {
            $actor ??= Auth::user();

            AuditEntry::query()->insert([
                'user_id'      => $actor['id'] ?? null,
                'actor_name'   => $actor['name'] ?? ($actor === null ? 'System' : null),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId !== null ? (string) $subjectId : null,
                'meta'         => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip'           => self::clientIp(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging is best-effort; swallow so it never breaks the action.
        }
    }

    private static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : null;
    }
}
