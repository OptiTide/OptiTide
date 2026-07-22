<?php

namespace App\Services\Mail;

use App\Core\View;

/**
 * Fluent mail builder:
 *
 *   Mail::to($email, $name)
 *       ->subject('Invoice INV-000001')
 *       ->view('emails.invoice', ['invoice' => $invoice])
 *       ->attach($pdf, 'INV-000001.pdf', 'application/pdf')
 *       ->send();
 */
final class Mail
{
    protected MailMessage $message;

    public function __construct()
    {
        $this->message = new MailMessage();
        $this->message->replyTo = config('mail.reply_to');
    }

    public static function to(string $email, ?string $name = null): self
    {
        $mail = new self();
        $mail->message->toEmail = $email;
        $mail->message->toName = $name;

        return $mail;
    }

    public function subject(string $subject): self
    {
        $this->message->subject = $subject;

        return $this;
    }

    public function replyTo(string $email): self
    {
        $this->message->replyTo = $email;

        return $this;
    }

    public function html(string $html): self
    {
        $this->message->html = $html;

        return $this;
    }

    /** Render an email view (wrapped in emails.layout) into the HTML body. */
    public function view(string $template, array $data = []): self
    {
        $this->message->html = View::render($template, $data);

        return $this;
    }

    public function text(string $text): self
    {
        $this->message->text = $text;

        return $this;
    }

    /**
     * Keep this message's body OUT of the email log.
     *
     * For mail whose body is itself a credential — the 2FA sign-in code. The
     * log still records that the email was sent, to whom, and whether it
     * delivered; only the secret is withheld. See MailMessage::$logBody.
     */
    public function withoutBodyLogging(): self
    {
        $this->message->logBody = false;

        return $this;
    }

    public function attach(string $content, string $filename, string $contentType = 'application/pdf'): self
    {
        $this->message->attachments[] = compact('content', 'filename', 'contentType');

        return $this;
    }

    public function send(): bool
    {
        return static::mailer()->send($this->message);
    }

    /**
     * The configured driver, wrapped in LoggingMailer so every send is recorded
     * in email_logs. Wrapping here rather than at each call site is what makes
     * "track all emails" actually true: nothing sends without going through this
     * method, so nothing can send unlogged — including code written later by
     * someone who has never heard of the log.
     *
     * Can be disabled with MAIL_LOG_TO_DB=false, but defaults ON. An audit trail
     * you have to remember to switch on is not an audit trail.
     */
    public static function mailer(): Mailer
    {
        $driver = config('mail.driver') === 'resend' ? 'resend' : 'log';
        $mailer = $driver === 'resend' ? new ResendMailer() : new LogMailer();

        if (! config('mail.log_to_db', true)) {
            return $mailer;
        }

        return new LoggingMailer($mailer, $driver);
    }
}
