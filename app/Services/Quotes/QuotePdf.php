<?php

namespace App\Services\Quotes;

use App\Core\View;
use App\Models\Client;
use App\Models\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders a quote PDF from the pdf.quote template via dompdf. */
final class QuotePdf
{
    public function render(int|string $quoteId): string
    {
        $quote = Quote::findOrFail($quoteId);

        $html = View::render('pdf.quote', [
            'quote'   => $quote,
            'client'  => Client::find($quote['client_id']),
            'items'   => Quote::items($quoteId),
            'company' => config('company'),
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
