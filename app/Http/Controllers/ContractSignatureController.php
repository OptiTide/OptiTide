<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owns the contract signing flow with proper auth + per-record ownership,
 * replacing the sign-pad package's shared class-token route (which is the
 * same token for every contract of a class).
 */
class ContractSignatureController extends Controller
{
    public function show(Contract $contract): mixed
    {
        $this->authorizeOwner($contract);

        if ($contract->isSigned()) {
            return redirect()->route('filament.client.resources.contracts.index');
        }

        return view('contracts.sign', ['contract' => $contract]);
    }

    public function store(Request $request, Contract $contract, GenerateSignatureDocumentAction $generateDocument): RedirectResponse
    {
        $this->authorizeOwner($contract);

        abort_if($contract->hasBeenSigned(), 409, 'This agreement has already been signed.');

        $validated = $request->validate([
            'sign' => ['required', 'string'],
        ]);

        $parts = explode(',', $validated['sign']);
        $decoded = count($parts) === 2 ? base64_decode($parts[1], true) : false;

        abort_if($decoded === false, 422, 'Invalid signature image.');

        $uuid = Str::uuid()->toString();

        // Atomic: if PDF generation throws, the signature row rolls back too,
        // so a partial failure never bricks the contract (the dangling
        // signature would otherwise make hasBeenSigned() block every retry).
        // The stored PNG on disk is harmless if orphaned.
        DB::transaction(function () use ($contract, $request, $uuid, $decoded, $generateDocument) {
            $signature = $contract->signature()->create([
                'uuid' => $uuid,
                'from_ips' => $request->ips(),
                'filename' => "{$uuid}.png",
                'certified' => config('sign-pad.certify_documents'),
            ]);

            Storage::disk(config('sign-pad.disk_name'))
                ->put($signature->getSignatureImagePath(), $decoded);

            // Renders the agreement template and stamps the signature into a PDF.
            $generateDocument($signature, $contract->getSignatureDocumentTemplate(), $decoded);

            $contract->markSigned();
        });

        return redirect()
            ->route('filament.client.resources.contracts.index')
            ->with('success', 'Thank you — your agreement has been signed.');
    }

    public function download(Contract $contract): StreamedResponse
    {
        // The signed PDF is a legal record with client PII — restrict to the
        // owner or an Admin. VAs are staff but must not enumerate every
        // client's contract.
        abort_unless(
            $contract->user_id === auth()->id() || auth()->user()?->role === \App\Enums\UserRole::Admin,
            403,
        );

        $signature = $contract->signature;
        $path = $signature?->getSignedDocumentPath();

        abort_if($path === null, 404, 'No signed document is available yet.');

        $disk = Storage::disk(config('sign-pad.disk_name'));
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, "agreement-{$contract->id}.pdf");
    }

    protected function authorizeOwner(Contract $contract): void
    {
        abort_unless($contract->user_id === auth()->id(), 403);
    }
}
