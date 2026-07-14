<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'price',
        'currency',
        'billing_interval',
        'features',
        'onboarding_form_key',
        'stripe_product_id',
        'stripe_price_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'price' => MoneyCast::class,
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function isSubscription(): bool
    {
        return $this->billing_interval !== null;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSubscriptions(Builder $query): Builder
    {
        return $query->whereNotNull('billing_interval');
    }
}
