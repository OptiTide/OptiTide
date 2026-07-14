<?php

namespace App\Models;

use App\Enums\OrderState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStateTransition extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'from_state',
        'to_state',
        'actor_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_state' => OrderState::class,
            'to_state' => OrderState::class,
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
