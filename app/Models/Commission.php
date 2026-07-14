<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CommissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'order_id',
        'amount',
        'currency',
        'rate_basis_points',
        'status',
        'notes',
        'approved_at',
        'settled_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'currency' => 'AUD',
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'status' => CommissionStatus::class,
            'approved_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
