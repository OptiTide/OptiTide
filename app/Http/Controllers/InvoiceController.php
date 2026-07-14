<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(protected InvoicePdf $pdf) {}

    /** Download an invoice PDF. Owner or staff only; drafts are staff-only. */
    public function download(Invoice $invoice): Response
    {
        $user = Auth::user();
        $isOwner = $invoice->user_id === $user->id;

        abort_unless($isOwner || $user->isStaff(), 403);
        // Clients never see drafts.
        abort_if($isOwner && ! $user->isStaff() && $invoice->status->value === 'draft', 403);

        return response($this->pdf->bytes($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->pdf->filename($invoice).'"',
        ]);
    }
}
