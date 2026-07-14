<?php

namespace App\Models;

use App\Enums\ArtifactStatus;
use App\Enums\ArtifactType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedArtifact extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'status',
        'content',
        'prompt_context',
        'version',
        'approved_by',
        'approved_at',
        'github_repo_url',
    ];

    protected function casts(): array
    {
        return [
            'type' => ArtifactType::class,
            'status' => ArtifactStatus::class,
            'prompt_context' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(MockupAnnotation::class);
    }
}
