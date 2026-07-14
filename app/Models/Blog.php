<?php

namespace App\Models;

use App\Enums\BlogStatus;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'scheduled_for',
        'published_at',
        'author_id',
        'meta',
        'is_ai_generated',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogStatus::class,
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'meta' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $blog) {
            if (blank($blog->slug)) {
                $blog->slug = static::uniqueSlug(Str::slug($blog->title));
            }
        });
    }

    /** A slug that doesn't collide with the unique column (appends -2, -3, …). */
    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'article';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', BlogStatus::Published);
    }

    /** Scheduled articles whose publish time has arrived; used by the daily cron. */
    public function scopeDueForPublishing(Builder $query): Builder
    {
        return $query->where('status', BlogStatus::Scheduled)
            ->where('scheduled_for', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === BlogStatus::Published;
    }

    // --- SEO helpers: fall back to sensible defaults when meta is unset. ---

    public function metaTitle(): string
    {
        return $this->meta['meta_title'] ?? $this->title;
    }

    public function metaDescription(): string
    {
        if (! blank($this->meta['meta_description'] ?? null)) {
            return $this->meta['meta_description'];
        }

        // Keep block boundaries as spaces so stripped text doesn't run together.
        $source = $this->excerpt ?: $this->body ?: '';
        $spaced = preg_replace('#</(p|h2|h3|h4|li|blockquote|div)>#i', ' $0', $source);

        return Str::of(strip_tags($spaced))->squish()->limit(155)->value();
    }

    public function ogImage(): ?string
    {
        return $this->meta['og_image'] ?? null;
    }

    /** @return array<int, string> */
    public function focusKeywords(): array
    {
        return array_values(array_filter((array) ($this->meta['focus_keywords'] ?? [])));
    }

    /**
     * Body HTML sanitised for public rendering via a DOM-based allow-list (see
     * HtmlSanitizer). VA approval is the human gate; this is defence-in-depth
     * against a prompt-injected/misbehaving model. Regex sanitising was replaced
     * because it is fundamentally bypassable (entity-encoded schemes,
     * slash-separated attributes, etc.).
     */
    public function safeBody(): string
    {
        return app(HtmlSanitizer::class)->clean($this->body ?? '');
    }
}

