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

    /**
     * The company e-mail set in admin Settings wins over the .env default —
     * otherwise the address shown on the site and invoices differs from the one
     * mail actually sends from, and customer replies go to a dead mailbox.
     * Note: the domain must still be authorised (SPF/DKIM) at your mail
     * provider, or the send will be rejected/spam-filed.
     */
    public function fromHeader(): string
    {
        $address = config('company.email') ?: config('mail.from.address', 'Hello@OptiTide.io');
        $name = config('company.brand_name') ?: config('mail.from.name', 'OptiTide');

        return $name ? sprintf('%s <%s>', $name, $address) : $address;
    }
}
