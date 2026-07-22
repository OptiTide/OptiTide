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
     * The provider's own id for the accepted message, set by the driver after a
     * successful send. Carried back on the message rather than returned, so the
     * Mailer interface stays a plain bool and no existing caller changes.
     * LoggingMailer stores it as the join key for future delivery webhooks.
     */
    public ?string $providerMessageId = null;

    /**
     * Whether the rendered body may be stored in the email log.
     *
     * Default true. Set false for a message whose body IS a secret — the 2FA
     * sign-in code is the case this exists for: it is deliberately hashed at
     * rest in the cache, so storing the plaintext in a browsable admin table
     * would make that table the only cleartext copy of a live second factor.
     *
     * A flag rather than a redaction pattern, because the secret is a bare
     * 6-digit run with nothing to anchor on. A regex broad enough to catch it
     * would also mangle invoice numbers, amounts, dates and phone numbers in
     * every other email. The sender knows what it is sending; the sanitiser
     * cannot.
     *
     * The metadata row is still written — you keep "a code was sent to X at
     * 10:04, and it delivered", which is the part with audit value.
     */
    public bool $logBody = true;

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
