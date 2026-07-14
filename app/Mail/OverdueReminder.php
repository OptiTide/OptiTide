<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueReminder extends Mailable
{
    use Queueable, SerializesModels;

    /** @param string $body the Claude-drafted reminder body (plain text) */
    public function __construct(public Invoice $invoice, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment reminder — invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        // markdown:, not view: — the template uses <x-mail::message> components,
        // which only resolve when rendered through the markdown mail pipeline.
        return new Content(markdown: 'emails.overdue-reminder');
    }
}
