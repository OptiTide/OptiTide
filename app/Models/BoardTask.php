<?php

namespace App\Models;

use App\Enums\BoardTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardTask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'position',
        'assigned_to',
        'created_by',
        'order_id',
        'due_date',
        'meta',
    ];

    protected $attributes = [
        'status' => 'backlog',
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => BoardTaskStatus::class,
            'due_date' => 'date',
            'meta' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
