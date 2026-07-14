<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockupAnnotation extends Model
{
    protected $fillable = [
        'generated_artifact_id',
        'user_id',
        'x',
        'y',
        'comment',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'decimal:4',
            'y' => 'decimal:4',
            'is_resolved' => 'boolean',
        ];
    }

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(GeneratedArtifact::class, 'generated_artifact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
