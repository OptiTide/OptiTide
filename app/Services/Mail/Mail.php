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

    public function attach(string $content, string $filename, string $contentType = 'application/pdf'): self
    {
        $this->message->attachments[] = compact('content', 'filename', 'contentType');

        return $this;
    }

    public function send(): bool
    {
        return static::mailer()->send($this->message);
    }

    public static function mailer(): Mailer
    {
        return config('mail.driver') === 'resend'
            ? new ResendMailer()
            : new LogMailer();
    }
}
