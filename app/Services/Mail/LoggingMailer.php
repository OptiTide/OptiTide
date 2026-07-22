<?php

namespace App\Services\Mail;

use App\Models\EmailLog;

/**
 * Wraps the real Mailer and records every attempt in email_logs.
 *
 * A decorator rather than logging inside each driver, so there is exactly one
 * implementation to keep correct and a new driver gets logging for free. And a
 * decorator rather than logging at each call site, because "log every email"
 * only holds if it cannot be forgotten — every send in the app funnels through
 * Mail::send(), so wrapping the mailer is the one place that catches all of it.
 *
 * THE LOG MUST NEVER BREAK THE SEND. Every database call here is inside a
 * try/catch: a full disk or a missing table has to degrade to "email sent but
 * not recorded", never "invoice not delivered because bookkeeping failed". The
 * log is an observer, and an observer that can veto the thing it observes is a
 * liability.
 */
final class LoggingMailer implements Mailer
{
    public function __construct(private Mailer $inner, private string $label = '')
    {
    }

    public function send(MailMessage $message): bool
    {
        // Insert BEFORE sending. If the driver hangs or the process is killed
        // mid-flight, the attempt survives as a 'sending' row instead of leaving
        // no trace of the send you would most want to know about.
        $id = $this->begin($message);

        try {
            $ok = $this->inner->send($message);
        } catch (\Throwable $e) {
            // The drivers are written to fail closed rather than throw, but a
            // decorator must not assume that of every future implementation.
            $this->finish($id, false, $message, $e->getMessage());

            throw $e;
        }

        $this->finish($id, $ok, $message, null);

        return $ok;
    }

    private function begin(MailMessage $message): ?int
    {
        try {
            // insert() rather than create(): create() follows the INSERT with a
            // find() that reads the whole row back, including the body we just
            // wrote. That is a wasted round trip and a wasted copy of the largest
            // column on the table, on every single email. We only need the id.
            $now = now();
            $id = EmailLog::query()->insert([
                'created_at'  => $now,
                'updated_at'  => $now,
                'to_email'    => $message->toEmail,
                'to_name'     => $message->toName,
                'from_email'  => $message->fromHeader(),
                'reply_to'    => $message->replyTo,
                'subject'     => $message->subject,
                'mailer'      => $this->label,
                'status'      => EmailLog::STATUS_SENDING,
                // Redacted at the point of storage, not on the way out — a body
                // that never contains a live token cannot leak one through a
                // route that forgets to redact.
                'body_html'   => EmailLog::redact($message->html),
                // Names and sizes only. Attachment bodies are invoice PDFs; they
                // are regenerable, and storing them would multiply the table size
                // for no answer anyone needs.
                'attachments' => $message->attachments === [] ? null : json_encode(array_map(fn ($a) => [
                    'filename' => $a['filename'] ?? '',
                    'bytes'    => strlen((string) ($a['content'] ?? '')),
                ], $message->attachments)),
            ]);

            return $id !== '' ? (int) $id : null;
        } catch (\Throwable $e) {
            logger('email log: could not record attempt', ['error' => $e->getMessage(), 'to' => $message->toEmail]);

            return null;
        }
    }

    private function finish(?int $id, bool $ok, MailMessage $message, ?string $error): void
    {
        if ($id === null) {
            return;
        }

        try {
            EmailLog::updateById($id, [
                'status'              => $ok ? EmailLog::STATUS_SENT : EmailLog::STATUS_FAILED,
                'provider_message_id' => $message->providerMessageId,
                // On failure with no exception, the driver has already written the
                // detail to the application log; record that rather than inventing
                // a message that claims to know more than we do.
                'error'               => $ok ? null : ($error ?: 'Send reported failure — see application log for the driver response.'),
            ]);
        } catch (\Throwable $e) {
            logger('email log: could not record outcome', ['error' => $e->getMessage(), 'id' => $id]);
        }
    }
}
