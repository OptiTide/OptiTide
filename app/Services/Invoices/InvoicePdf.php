<?php

namespace App\Services\Invoices;

use App\Core\View;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Payments\PaymentManager;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders an AU tax-invoice PDF from the pdf.invoice template via dompdf. */
final class InvoicePdf
{
    public function render(int|string $invoiceId): string
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $client = Client::find($invoice['client_id']);
        $items = Invoice::items($invoiceId);

        $html = View::render('pdf.invoice', [
            'invoice'      => $invoice,
            'client'       => $client,
            'items'        => $items,
            'company'      => config('company'),
            'instructions' => (new PaymentManager())->instructionsFor($invoice),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
