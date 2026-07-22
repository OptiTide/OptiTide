<?php

namespace App\Services\Mail;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends through an SMTP server (mail.optitide.io) using PHPMailer.
 *
 * PHPMailer rather than a hand-rolled socket client, deliberately. Invoices go
 * out of here with PDF attachments, which means MIME multipart, base64 chunking
 * at the right line length, dot-stuffing in DATA, CRLF discipline, and RFC 2047
 * header encoding for any non-ASCII name or subject. Each of those is easy to
 * get subtly wrong in a way that does not fail loudly — it just delivers a
 * corrupt attachment or a mangled subject to a paying client. It also gives
 * header-injection protection for free: a recipient name containing a newline
 * would otherwise let an attacker inject their own headers.
 *
 * Fails CLOSED and never throws, matching ResendMailer: a missing or wrong
 * credential logs and reports failure rather than 500ing whatever request flow
 * happened to be sending. The email log records the failure either way.
 */
final class SmtpMailer implements Mailer
{
    public function send(MailMessage $message): bool
    {
        $host = trim((string) config('mail.smtp.host', ''));
        $username = trim((string) config('mail.smtp.username', ''));
        $password = (string) config('mail.smtp.password', '');

        if ($host === '' || $username === '' || $password === '') {
            // Never log the password, or any hint of its length.
            logger('SMTP is not configured — email not sent.', [
                'to'         => $message->toEmail,
                'subject'    => $message->subject,
                'host_set'   => $host !== '',
                'user_set'   => $username !== '',
                'pass_set'   => $password !== '',
            ]);

            return false;
        }

        $mail = new PHPMailer(true);   // true => throw, caught below

        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int) config('mail.smtp.port', 587);
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Timeout = (int) config('mail.smtp.timeout', 20);

            // 'tls' = STARTTLS on 587 (upgrade a plaintext connection);
            // 'ssl' = implicit TLS on 465 (encrypted from the first byte).
            // Anything else means no encryption, which would put the password
            // on the wire in the clear — refuse rather than silently downgrade.
            $encryption = strtolower(trim((string) config('mail.smtp.encryption', 'tls')));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                logger('SMTP encryption must be "tls" or "ssl" — refusing to send credentials unencrypted.', [
                    'configured' => $encryption,
                ]);

                return false;
            }

            // From must be an address the server is willing to send as; the
            // display name is separate. fromHeader() renders "Name <addr>",
            // so split it back out rather than handing PHPMailer the whole
            // string, which it would treat as an address and reject.
            [$fromAddress, $fromName] = $this->splitFrom($message->fromHeader());
            $mail->setFrom($fromAddress, $fromName, false);

            $mail->addAddress($message->toEmail, (string) ($message->toName ?? ''));

            if ($message->replyTo !== null && trim($message->replyTo) !== '') {
                $mail->addReplyTo($message->replyTo);
            }

            $mail->Subject = $message->subject;
            $mail->isHTML(true);
            $mail->Body = $message->html;

            // A text/plain alternative materially improves deliverability, and
            // several spam filters penalise HTML-only mail. Fall back to a
            // generated one rather than sending none.
            $mail->AltBody = $message->text !== null && trim($message->text) !== ''
                ? $message->text
                : trim(html_entity_decode(strip_tags(preg_replace('~<br\s*/?>|</p>|</div>|</tr>~i', "\n", $message->html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            foreach ($message->attachments as $a) {
                $mail->addStringAttachment(
                    (string) ($a['content'] ?? ''),
                    (string) ($a['filename'] ?? 'attachment'),
                    PHPMailer::ENCODING_BASE64,
                    (string) ($a['contentType'] ?? 'application/octet-stream')
                );
            }

            $mail->send();

            // The Message-ID this server assigned. Stored by LoggingMailer, and
            // it is what correlates a row here with a line in the mail server's
            // own logs when a client says nothing arrived.
            $id = trim((string) $mail->getLastMessageID());
            if ($id !== '') {
                $message->providerMessageId = $id;
            }

            return true;
        } catch (PHPMailerException $e) {
            // ErrorInfo carries the server's own refusal ("relay denied",
            // "authentication failed"), which is the useful half.
            logger('SMTP send failed.', [
                'to'        => $message->toEmail,
                'subject'   => $message->subject,
                'error'     => $e->getMessage(),
                'smtp_info' => $mail->ErrorInfo,
            ]);

            return false;
        } catch (\Throwable $e) {
            logger('SMTP send failed (unexpected).', ['to' => $message->toEmail, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * "OptiTide <hello@optitide.io>" => ['hello@optitide.io', 'OptiTide'].
     * A bare address with no display name comes back with an empty name.
     */
    private function splitFrom(string $header): array
    {
        if (preg_match('/^\s*(.*?)\s*<\s*([^>]+)\s*>\s*$/', $header, $m)) {
            return [trim($m[2]), trim($m[1], " \t\"")];
        }

        return [trim($header), ''];
    }
}
