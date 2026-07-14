<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'blog_id',
        'platform',
        'content',
        'image_prompt',
        'image_path',
        'status',
        'scheduled_for',
        'published_at',
        'external_id',
        'error',
    ];

    protected $attributes = [
        'status' => 'pending_review',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => SocialPostStatus::class,
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Approved/scheduled posts due for distribution. A null scheduled_for means
     * "send at the next run" — otherwise `NULL <= now()` is never true in SQL
     * and an approved-without-a-date post would be silently stranded.
     */
    public function scopeDueForPublishing(Builder $query): Builder
    {
        return $query->whereIn('status', [SocialPostStatus::Approved, SocialPostStatus::Scheduled])
            ->where(function (Builder $q) {
                $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now());
            });
    }
}
