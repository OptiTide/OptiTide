<?php

namespace App\Services\Mail;

final class MailMessage
{
    public string $toEmail = '';
    public ?string $toName = null;
    public string $subject = '';
    public string $html = '';
    public ?string $text = null;
    public ?string $replyTo = null;

    /** @var array<int,array{filename:string,content:string,contentType:string}> */
    public array $attachments = [];

    public function fromHeader(): string
    {
        $name = config('mail.from.name', 'OptiTide');
        $address = config('mail.from.address', 'Hello@OptiTide.io');

        return $name ? sprintf('%s <%s>', $name, $address) : $address;
    }
}
