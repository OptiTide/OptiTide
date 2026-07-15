<?php

namespace App\Services\Mail;

/**
 * Sends through the Resend HTTP API (https://resend.com) with plain cURL — no
 * SDK dependency. Fails closed: with no API key it logs and reports failure
 * rather than throwing, so a missing credential never 500s a request flow.
 */
final class ResendMailer implements Mailer
{
    public function send(MailMessage $message): bool
    {
        $apiKey = config('mail.resend.api_key');

        if (! $apiKey) {
            logger('Resend API key not set — email not sent.', ['to' => $message->toEmail, 'subject' => $message->subject]);

            return false;
        }

        $payload = [
            'from'    => $message->fromHeader(),
            'to'      => [$message->toName ? "{$message->toName} <{$message->toEmail}>" : $message->toEmail],
            'subject' => $message->subject,
            'html'    => $message->html,
        ];

        if ($message->text !== null) {
            $payload['text'] = $message->text;
        }
        if ($message->replyTo !== null) {
            $payload['reply_to'] = $message->replyTo;
        }
        if ($message->attachments !== []) {
            $payload['attachments'] = array_map(fn ($a) => [
                'filename' => $a['filename'],
                'content'  => base64_encode($a['content']),
            ], $message->attachments);
        }

        $ch = curl_init(config('mail.resend.endpoint', 'https://api.resend.com/emails'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status >= 300) {
            logger('Resend send failed.', ['status' => $status, 'error' => $error, 'body' => is_string($response) ? substr($response, 0, 500) : null]);

            return false;
        }

        return true;
    }
}
