<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Renders an Invoice to a PDF document (used by the download endpoint, the
 * "send invoice" flow, and overdue reminder attachments).
 */
class InvoicePdf
{
    public function bytes(Invoice $invoice): string
    {
        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice->loadMissing(['items', 'user', 'order']),
        ])->output();
    }

    public function filename(Invoice $invoice): string
    {
        return "invoice-{$invoice->invoice_number}.pdf";
    }
}
