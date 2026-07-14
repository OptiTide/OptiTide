<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Spatie\Onboard\Concerns\GetsOnboarded;
use Spatie\Onboard\Concerns\Onboardable;

#[Fillable([
    'name', 'email', 'password', 'role', 'company_name', 'phone',
    'locale', 'preferred_currency', 'referred_by', 'onboarded_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, Onboardable
{
    /** @use HasFactory<UserFactory> */
    use Billable, GetsOnboarded, HasFactory, Notifiable;

    /** Fresh instances (e.g. OAuth signups) get a usable role before a DB round-trip. */
    protected $attributes = [
        'role' => 'client',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'onboarded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Every client gets a unique referral code for the affiliate program.
        static::created(function (self $user) {
            if ($user->referral_code === null) {
                $user->forceFill(['referral_code' => static::generateReferralCode()])->saveQuietly();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role->isStaff(),
            'client' => true,
            default => false,
        };
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarded_at !== null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function helpdeskTickets(): HasMany
    {
        return $this->hasMany(HelpdeskTicket::class);
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(ReferralRelationship::class, 'referrer_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'referrer_id');
    }

    /** The shareable affiliate link that attributes new signups to this user. */
    public function referralUrl(): string
    {
        return url('/').'?ref='.$this->referral_code;
    }
}
