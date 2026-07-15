<?php

namespace App\Services\Mail;

/** Writes rendered emails to storage/logs/mail.log — used in local dev. */
final class LogMailer implements Mailer
{
    public function send(MailMessage $message): bool
    {
        $entry = sprintf(
            "==== %s ====\nTo: %s <%s>\nFrom: %s\nSubject: %s\nAttachments: %s\n\n%s\n\n",
            now(),
            $message->toName ?? '',
            $message->toEmail,
            $message->fromHeader(),
            $message->subject,
            implode(', ', array_column($message->attachments, 'filename')) ?: 'none',
            $message->html
        );

        @file_put_contents(storage_path('logs/mail.log'), $entry, FILE_APPEND);

        return true;
    }
}
