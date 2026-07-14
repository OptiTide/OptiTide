<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your OptiTide invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        // markdown:, not view: — the template uses <x-mail::message> components,
        // which only resolve when rendered through the markdown mail pipeline.
        return new Content(markdown: 'emails.invoice-issued');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(InvoicePdf::class)->bytes($this->invoice),
                app(InvoicePdf::class)->filename($this->invoice),
            )->withMime('application/pdf'),
        ];
    }
}
