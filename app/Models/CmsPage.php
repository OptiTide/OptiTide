<?php

namespace App\Models;

use App\Enums\CmsPageStatus;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class CmsPage extends Model
{
    use HasFactory, HasTranslations;

    /** Resolved per the app locale (fallback to config fallback_locale). */
    public array $translatable = ['title', 'body'];

    protected $fillable = [
        'slug',
        'title',
        'body',
        'excerpt',
        'status',
        'show_in_footer',
        'meta',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => CmsPageStatus::class,
            'show_in_footer' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page) {
            if (blank($page->slug)) {
                // getTranslation avoids the array that HasTranslations returns
                // for a translatable attribute at creation time.
                $page->slug = static::uniqueSlug(Str::slug($page->getTranslation('title', app()->getLocale())));
            }
        });

        // The footer link list is cached (rendered on every public page).
        static::saved(fn () => Cache::forget('cms:footer_pages'));
        static::deleted(fn () => Cache::forget('cms:footer_pages'));
    }

    /**
     * Cached footer link list. Only plain arrays (slug + title) are cached — an
     * Eloquent Collection of HasTranslations models serialises to a
     * __PHP_Incomplete_Class under the database cache driver. Titles resolve to
     * the active locale when the cache is (re)populated; the saved/deleted hooks
     * above clear the key on any change.
     */
    public static function footerPages(): Collection
    {
        $rows = Cache::rememberForever('cms:footer_pages', fn () => static::inFooter()
            ->get(['slug', 'title'])
            ->map(fn ($page) => ['slug' => $page->slug, 'title' => $page->title])
            ->toArray());

        // Hydrate rows to objects so callers keep using ->slug / ->title
        // (store-layout.blade.php iterates with object access).
        return new Collection(array_map(fn ($row) => (object) $row, $rows));
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'page';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CmsPageStatus::Published);
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true)->where('status', CmsPageStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === CmsPageStatus::Published;
    }

    /** Body HTML sanitised through the DOM allow-list for public rendering. */
    public function safeBody(): string
    {
        return app(HtmlSanitizer::class)->clean((string) $this->body);
    }

    public function metaTitle(): string
    {
        // Falls back to the translatable title (per-locale) + brand, so a
        // localised page doesn't emit an English <title>.
        return $this->meta['meta_title'] ?? $this->title.' — OptiTide';
    }

    public function metaDescription(): string
    {
        return $this->meta['meta_description']
            ?? Str::of(strip_tags((string) $this->body))->squish()->limit(155)->value();
    }
}
