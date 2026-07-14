<?php

namespace App\Filament\Client\Pages;

use App\Enums\CommissionStatus;
use App\Support\Money;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Client-facing "refer & earn": the user's shareable referral link, referral
 * count, and commission earnings broken down by status. All data is derived
 * from the authenticated user's own relations — no record-selecting property,
 * so no IDOR surface. Taking a commission as credit lives on the (owner-scoped)
 * commissions resource.
 */
class Affiliate extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Refer & earn';

    protected static ?string $title = 'Refer & earn';

    protected string $view = 'filament.client.pages.affiliate';

    #[Computed]
    public function referralUrl(): string
    {
        return Auth::user()->referralUrl();
    }

    #[Computed]
    public function referralCount(): int
    {
        return Auth::user()->referralsMade()->count();
    }

    /** @return array<string, Money> earnings summed per status bucket */
    #[Computed]
    public function totals(): array
    {
        $commissions = Auth::user()->commissions()->get();

        // Seed from the referrer's own commission currency, not a hardcoded AUD
        // zero — otherwise a non-AUD commission would throw on Money::add().
        $currency = $commissions->first()?->amount->currency ?? 'AUD';

        $sum = fn (CommissionStatus $status): Money => $commissions
            ->filter(fn ($c) => $c->status === $status)
            ->reduce(fn (Money $carry, $c) => $carry->add($c->amount), Money::zero($currency));

        return [
            'pending' => $sum(CommissionStatus::Pending),
            'approved' => $sum(CommissionStatus::Approved),
            'credited' => $sum(CommissionStatus::Credited),
            'paid' => $sum(CommissionStatus::Paid),
        ];
    }
}
