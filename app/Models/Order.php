<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderState;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStateTransition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'payment_status',
        'subtotal',
        'total',
        'notes',
        'meta',
        'placed_at',
    ];

    protected $attributes = [
        'state' => 'pending_intake',
        'payment_status' => 'pending',
        'currency' => 'AUD',
    ];

    protected function casts(): array
    {
        return [
            'state' => OrderState::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => MoneyCast::class,
            'total' => MoneyCast::class,
            'meta' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->order_number ??= 'PENDING-'.Str::uuid();
        });

        static::created(function (self $order) {
            if (Str::startsWith($order->order_number, 'PENDING-')) {
                $order->forceFill([
                    'order_number' => sprintf('OT-%06d', $order->id),
                ])->saveQuietly();
            }
        });
    }

    /**
     * Move the order through the CRM pipeline, enforcing the allowed
     * transition map and recording an audit trail entry atomically.
     *
     * @throws InvalidStateTransition
     */
    public function transitionTo(OrderState $to, ?User $actor = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($to, $actor, $notes) {
            $from = $this->state;

            if (! $from->canTransitionTo($to)) {
                throw InvalidStateTransition::make($this->order_number, $from, $to);
            }

            // Compare-and-swap: if a concurrent request already moved the
            // order, zero rows match and the transition is rejected rather
            // than silently overwriting it. (Portable to SQLite, unlike
            // lockForUpdate.)
            $updated = static::whereKey($this->getKey())
                ->where('state', $from->value)
                ->update(['state' => $to->value, 'updated_at' => $this->freshTimestamp()]);

            if ($updated === 0) {
                throw InvalidStateTransition::make($this->order_number, $from, $to);
            }

            $this->refresh();

            $this->stateTransitions()->create([
                'from_state' => $from,
                'to_state' => $to,
                'actor_id' => $actor?->getKey(),
                'notes' => $notes,
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stateTransitions(): HasMany
    {
        return $this->hasMany(OrderStateTransition::class)->latest('created_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ClientSubmission::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(GeneratedArtifact::class);
    }

    public function latestArtifact(\App\Enums\ArtifactType $type): ?GeneratedArtifact
    {
        return $this->artifacts()->where('type', $type)->latest('id')->first();
    }

    public function helpdeskTickets(): HasMany
    {
        return $this->hasMany(HelpdeskTicket::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** Onboarding cannot be finalized while an agreement awaits signature. */
    public function hasUnsignedContract(): bool
    {
        return $this->contracts()->where('status', 'pending')->exists();
    }

    public function boardTasks(): HasMany
    {
        return $this->hasMany(BoardTask::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * The dynamic intake form this order should collect, resolved from the
     * first purchased product that declares an onboarding form.
     */
    public function onboardingFormSchema(): ?FormSchema
    {
        $keys = $this->items()
            ->whereHas('product', fn ($query) => $query->whereNotNull('onboarding_form_key'))
            ->with('product')
            ->get()
            ->pluck('product.onboarding_form_key')
            ->filter()
            ->unique();

        if ($keys->isEmpty()) {
            return null;
        }

        // When an order spans tiers, capture the superset of fields. The
        // seeded schemas nest: basic ⊂ intermediate ⊂ exhaustive.
        $priority = ['exhaustive_onboarding' => 3, 'intermediate_onboarding' => 2, 'basic_onboarding' => 1];
        $key = $keys->sortByDesc(fn (string $k) => $priority[$k] ?? 0)->first();

        return FormSchema::where('key', $key)->where('is_active', true)->first();
    }

    public function submission(): ?ClientSubmission
    {
        return $this->submissions()->latest('id')->first();
    }

    /**
     * True when the client still needs to submit their project brief: a paid
     * order sitting at intake, not blocked by an unsigned agreement, with a
     * form to fill. Gated on state (not on past submissions) so a VA can
     * bounce the order back to intake for a revised brief.
     */
    public function needsIntake(): bool
    {
        return $this->isPaid()
            && $this->state === OrderState::PendingIntake
            && ! $this->hasUnsignedContract()
            && $this->onboardingFormSchema() !== null;
    }
}
