<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMonitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'status',
        'consecutive_failures',
        'last_checked_at',
        'last_status_changed_at',
        'ssl_expires_at',
        'incident_ticket_id',
        'ssl_ticket_id',
        'is_active',
    ];

    protected $attributes = [
        'status' => 'unknown',
        'consecutive_failures' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'status' => MonitorStatus::class,
            'last_checked_at' => 'datetime',
            'last_status_changed_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function incidentTicket(): BelongsTo
    {
        return $this->belongsTo(HelpdeskTicket::class, 'incident_ticket_id');
    }

    public function sslTicket(): BelongsTo
    {
        return $this->belongsTo(HelpdeskTicket::class, 'ssl_ticket_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isDown(): bool
    {
        return $this->status === MonitorStatus::Down;
    }

    /** Whole days until the TLS certificate expires, or null if unknown. */
    public function sslDaysRemaining(): ?int
    {
        return $this->ssl_expires_at !== null
            ? (int) now()->startOfDay()->diffInDays($this->ssl_expires_at->startOfDay(), false)
            : null;
    }

    public function host(): ?string
    {
        return parse_url($this->url, PHP_URL_HOST) ?: null;
    }
}
