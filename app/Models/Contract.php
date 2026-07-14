<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Creagia\LaravelSignPad\Concerns\RequiresSignature;
use Creagia\LaravelSignPad\Contracts\CanBeSigned;
use Creagia\LaravelSignPad\Contracts\ShouldGenerateSignatureDocument;
use Creagia\LaravelSignPad\SignatureDocumentTemplate;
use Creagia\LaravelSignPad\SignaturePosition;
use Creagia\LaravelSignPad\Templates\BladeDocumentTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model implements CanBeSigned, ShouldGenerateSignatureDocument
{
    use RequiresSignature;

    protected $fillable = [
        'user_id',
        'order_id',
        'title',
        'template_key',
        'status',
        'signed_at',
    ];

    protected $attributes = [
        'template_key' => 'service_agreement',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'signed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The signature pad posts to our own authenticated, ownership-checked
     * endpoint — never the package's class-token route.
     */
    public function getSignatureRoute(): string
    {
        return route('contracts.sign', $this);
    }

    public function getSignatureDocumentTemplate(): SignatureDocumentTemplate
    {
        return new SignatureDocumentTemplate(
            outputPdfPrefix: 'optitide-agreement',
            // Same partial renders the on-screen preview and the PDF body.
            template: new BladeDocumentTemplate('contracts.body.'.$this->template_key),
            signaturePositions: [
                // Right-hand "Signature:" column of the signing block (mm).
                // The body is designed to fit a single page so this stays aligned.
                new SignaturePosition(signaturePage: 1, signatureX: 112, signatureY: 244),
            ],
        );
    }

    public function markSigned(): void
    {
        $this->forceFill([
            'status' => ContractStatus::Signed,
            'signed_at' => now(),
        ])->save();
    }

    public function isSigned(): bool
    {
        return $this->status === ContractStatus::Signed;
    }
}
