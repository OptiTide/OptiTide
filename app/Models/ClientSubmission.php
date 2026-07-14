<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSubmission extends Model
{
    protected $fillable = [
        'order_id',
        'form_schema_id',
        'user_id',
        'data',
        'brand_assets',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'brand_assets' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formSchema(): BelongsTo
    {
        return $this->belongsTo(FormSchema::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
