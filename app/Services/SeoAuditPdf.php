<?php

namespace App\Services;

use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class SeoAuditPdf
{
    public function bytes(Lead $lead): string
    {
        return Pdf::loadView('leads.seo-audit', [
            'lead' => $lead,
            'audit' => $lead->meta['audit'] ?? [],
        ])->output();
    }

    public function filename(Lead $lead): string
    {
        $host = parse_url((string) $lead->website_url, PHP_URL_HOST) ?: 'website';

        return 'seo-audit-'.Str::slug($host).'.pdf';
    }
}
