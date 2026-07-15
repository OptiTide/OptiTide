<?php

namespace App\Services\Support;

/**
 * IMAP pull importer (optional). Fetches UNSEEN messages and turns each into a
 * ticket via EmailToTicketService, then marks them Seen. Requires the PHP imap
 * extension AND configured credentials — otherwise returns a clear error instead
 * of fataling (config-gated, like the platform's other integrations).
 */
final class EmailImportService
{
    public function import(int $limit = 50): array
    {
        if (! function_exists('imap_open')) {
            return ['imported' => 0, 'error' => 'The PHP imap extension is not installed. Use the /webhooks/email inbound webhook instead.'];
        }
        if (trim((string) config('mailbox.host', '')) === '' || trim((string) config('mailbox.username', '')) === '') {
            return ['imported' => 0, 'error' => 'IMAP mailbox is not configured (IMAP_HOST / IMAP_USERNAME).'];
        }

        $ref = sprintf(
            '{%s:%d/imap/%s}%s',
            config('mailbox.host'),
            (int) config('mailbox.port', 993),
            config('mailbox.ssl', true) ? 'ssl' : 'notls',
            config('mailbox.folder', 'INBOX')
        );

        $mbox = @imap_open($ref, (string) config('mailbox.username'), (string) config('mailbox.password'), 0, 1);
        if (! $mbox) {
            $err = function_exists('imap_last_error') ? (imap_last_error() ?: '') : '';

            return ['imported' => 0, 'error' => 'IMAP connection failed' . ($err ? ': ' . $err : '.')];
        }

        $ingest = new EmailToTicketService();
        $unseen = @imap_search($mbox, 'UNSEEN') ?: [];
        $count = 0;

        foreach (array_slice($unseen, 0, $limit) as $num) {
            $header = @imap_headerinfo($mbox, $num);
            if (! $header) {
                continue;
            }
            $email = strtolower(($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? ''));
            $name = $this->mimeDecode((string) ($header->fromaddress ?? ''));
            $subject = $this->mimeDecode((string) ($header->subject ?? ''));
            $body = $this->plainBody($mbox, $num);

            $ingest->ingest($email, $name, $subject, $body);
            @imap_setflag_full($mbox, (string) $num, "\\Seen");
            $count++;
        }

        @imap_close($mbox);

        return ['imported' => $count];
    }

    private function mimeDecode(string $value): string
    {
        if ($value === '' || ! function_exists('imap_mime_header_decode')) {
            return $value;
        }
        $out = '';
        foreach (imap_mime_header_decode($value) as $part) {
            $out .= $part->text;
        }

        return $out;
    }

    private function plainBody($mbox, int $num): string
    {
        $body = @imap_fetchbody($mbox, $num, '1');
        if (! $body) {
            $body = @imap_body($mbox, $num);
        }

        return trim((string) $body);
    }
}
