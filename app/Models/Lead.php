<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'website_url',
        'source',
        'status',
        'message',
        'seo_report_path',
        'assigned_to',
        'meta',
    ];

    protected $attributes = [
        'source' => 'contact_form',
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'meta' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
