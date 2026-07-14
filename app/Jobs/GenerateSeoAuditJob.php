<?php

namespace App\Jobs;

use App\Mail\SeoAuditReport;
use App\Models\Lead;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\AI\SeoAuditPromptBuilder;
use App\Services\SeoAuditPdf;
use App\Support\SafeUrlFetcher;
use App\Support\UnsafeUrlException;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fetches a prospect's URL (SSRF-guarded), extracts SEO signals, has Claude
 * produce a JSON audit, renders a branded PDF, stores it, and emails the
 * prospect. On any failure the lead is still captured (the email address is the
 * point) with the error recorded in meta — a broken report is never emailed.
 */
class GenerateSeoAuditJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $leadId) {}

    public function handle(
        SafeUrlFetcher $fetcher,
        ClaudeClient $claude,
        SeoAuditPromptBuilder $builder,
        SeoAuditPdf $pdf,
    ): void {
        $lead = Lead::find($this->leadId);

        if ($lead === null || blank($lead->website_url)) {
            return;
        }

        // Idempotency: seo_report_path is stamped only once the report has been
        // emailed, so a queue retry after success won't re-send a duplicate.
        if (! blank($lead->seo_report_path)) {
            return;
        }

        try {
            $html = $fetcher->fetch($lead->website_url);
        } catch (UnsafeUrlException $e) {
            $this->recordError($lead, 'fetch', $e->getMessage());

            return;
        }

        try {
            $signals = $this->extractSignals($html, $lead->website_url);
            $raw = $claude->generate($builder->system(), $builder->user($lead->website_url, $signals));
            $audit = json_decode($raw, true);

            if (! is_array($audit) || ! isset($audit['overall_score'], $audit['findings'])) {
                throw new ClaudeGenerationException('SEO audit returned malformed JSON.');
            }
        } catch (ClaudeGenerationException $e) {
            $this->recordError($lead, 'audit', $e->getMessage());

            return;
        }

        $meta = $lead->meta ?? [];
        $meta['audit'] = $audit;
        $meta['signals'] = $signals;
        unset($meta['audit_error']);
        $lead->forceFill(['meta' => $meta])->save();

        // Persist a copy to the private disk.
        $path = "seo_audits/{$lead->id}/".$pdf->filename($lead);
        Storage::disk(config('filesystems.private_disk'))->put($path, $pdf->bytes($lead->fresh()));

        Mail::to($lead->email)->send(new SeoAuditReport($lead->fresh()));

        // Stamp the path LAST — it's the "done" marker the idempotency guard reads.
        $lead->forceFill(['seo_report_path' => $path])->save();
    }

    public function failed(Throwable $e): void
    {
        if ($lead = Lead::find($this->leadId)) {
            $this->recordError($lead, 'job', $e->getMessage());
        }
    }

    protected function recordError(Lead $lead, string $stage, string $message): void
    {
        $lead->forceFill([
            'meta' => array_merge($lead->meta ?? [], ['audit_error' => ['stage' => $stage, 'message' => $message]]),
        ])->save();
    }

    /** @return array<string, mixed> */
    protected function extractSignals(string $html, string $url): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $title = trim($xpath->evaluate('string(//title)'));
        $metaDesc = trim($xpath->evaluate(
            'string(//meta[translate(@name, "DESCRIPTION", "description")="description"]/@content)'
        ));

        $images = $xpath->query('//img');
        $missingAlt = 0;
        foreach ($images as $img) {
            if (trim($img->getAttribute('alt')) === '') {
                $missingAlt++;
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

        return [
            'is_https' => str_starts_with(strtolower($url), 'https://'),
            'title' => $title !== '' ? $title : '(missing)',
            'title_length' => mb_strlen($title),
            'meta_description' => $metaDesc !== '' ? $metaDesc : '(missing)',
            'meta_description_length' => mb_strlen($metaDesc),
            'h1_count' => (int) $xpath->evaluate('count(//h1)'),
            'h2_count' => (int) $xpath->evaluate('count(//h2)'),
            'word_count' => $text === '' ? 0 : str_word_count($text),
            'image_count' => $images->length,
            'images_missing_alt' => $missingAlt,
            'has_viewport_meta' => $xpath->evaluate('count(//meta[@name="viewport"])') > 0,
            'has_canonical' => $xpath->evaluate('count(//link[@rel="canonical"])') > 0,
        ];
    }
}
