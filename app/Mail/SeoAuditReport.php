<?php

namespace App\Mail;

use App\Models\Lead;
use App\Services\SeoAuditPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SeoAuditReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        $host = parse_url((string) $this->lead->website_url, PHP_URL_HOST) ?: 'your website';

        return new Envelope(subject: "Your SEO audit for {$host}");
    }

    public function content(): Content
    {
        // markdown:, not view: — the template uses <x-mail::message> components.
        return new Content(markdown: 'emails.seo-audit-report');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(SeoAuditPdf::class)->bytes($this->lead),
                app(SeoAuditPdf::class)->filename($this->lead),
            )->withMime('application/pdf'),
        ];
    }
}
