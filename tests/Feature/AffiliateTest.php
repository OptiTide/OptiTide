<?php

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\ReferralRelationship;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function referrals(): ReferralService
{
    return app(ReferralService::class);
}

// ---------------------------------------------------------------------------
// ?ref capture
// ---------------------------------------------------------------------------

test('a valid ?ref drops a first-touch referral cookie', function () {
    $this->get('/?ref=ABCD1234')->assertCookie('referral', 'ABCD1234');
});

test('a malformed ?ref is ignored', function () {
    $this->get('/?ref=not-valid')->assertCookieMissing('referral');
});

// ---------------------------------------------------------------------------
// Registration attribution
// ---------------------------------------------------------------------------

test('attachReferral links the user, creates the relationship, and is guarded + idempotent', function () {
    $referrer = User::factory()->create(['role' => 'client']);
    $referred = User::factory()->create(['role' => 'client']);

    referrals()->attachReferral($referred, $referrer->referral_code);

    expect($referred->fresh()->referred_by)->toBe($referrer->id)
        ->and(ReferralRelationship::where('referred_id', $referred->id)->count())->toBe(1);

    // Idempotent re-attach — still one relationship.
    referrals()->attachReferral($referred, $referrer->referral_code);
    expect(ReferralRelationship::where('referred_id', $referred->id)->count())->toBe(1);
});

test('attachReferral no-ops on unknown code, self-referral, and already-attributed users', function () {
    $referrer = User::factory()->create(['role' => 'client']);
    $referred = User::factory()->create(['role' => 'client']);

    referrals()->attachReferral($referred, 'NOSUCHCD');      // unknown
    referrals()->attachReferral($referrer, $referrer->referral_code); // self

    expect($referred->fresh()->referred_by)->toBeNull()
        ->and($referrer->fresh()->referred_by)->toBeNull()
        ->and(ReferralRelationship::count())->toBe(0);
});

test('the client register page renders', function () {
    $this->get('/client/register')->assertOk();
});

// ---------------------------------------------------------------------------
// Commission on first paid order
// ---------------------------------------------------------------------------

test('a referred client\'s first paid order creates a percentage commission for the referrer', function () {
    $referrer = User::factory()->create(['role' => 'client']);
    $referred = User::factory()->create(['role' => 'client']);
    referrals()->attachReferral($referred, $referrer->referral_code);

    $order = Order::factory()->paid()->create(['user_id' => $referred->id, 'total' => 250_000, 'subtotal' => 250_000]);
    referrals()->recordCommissionForFirstPaidOrder($order);

    $commission = Commission::first();
    expect($commission)->not->toBeNull()
        ->and($commission->referrer_id)->toBe($referrer->id)
        ->and($commission->amount->amount)->toBe(25_000)       // 10% of 250,000 cents
        ->and($commission->rate_basis_points)->toBe(1000)
        ->and($commission->status)->toBe(CommissionStatus::Pending)
        ->and(ReferralRelationship::where('referred_id', $referred->id)->first()->converted_at)->not->toBeNull();
});

test('only the FIRST paid order earns a commission (idempotent)', function () {
    $referrer = User::factory()->create(['role' => 'client']);
    $referred = User::factory()->create(['role' => 'client']);
    referrals()->attachReferral($referred, $referrer->referral_code);

    $first = Order::factory()->paid()->create(['user_id' => $referred->id, 'total' => 100_000]);
    referrals()->recordCommissionForFirstPaidOrder($first);
    referrals()->recordCommissionForFirstPaidOrder($first); // webhook redelivery
    $second = Order::factory()->paid()->create(['user_id' => $referred->id, 'total' => 200_000]);
    referrals()->recordCommissionForFirstPaidOrder($second); // later order

    expect(Commission::count())->toBe(1);
});

test('an unreferred client generates no commission', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = Order::factory()->paid()->create(['user_id' => $client->id]);

    referrals()->recordCommissionForFirstPaidOrder($order);

    expect(Commission::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Status transitions
// ---------------------------------------------------------------------------

test('commission transitions are guarded and credited is terminal', function () {
    $service = app(CommissionService::class);
    $c = Commission::factory()->create(['status' => CommissionStatus::Pending]);

    $service->applyAsCredit($c); // not yet approved → no-op
    expect($c->fresh()->status)->toBe(CommissionStatus::Pending);

    $service->approve($c);
    expect($c->fresh()->status)->toBe(CommissionStatus::Approved);

    $service->approve($c); // already approved → no-op
    $service->applyAsCredit($c);
    expect($c->fresh()->status)->toBe(CommissionStatus::Credited)
        ->and($c->fresh()->settled_at)->not->toBeNull();

    // Credited is terminal — no cash payout on top of account credit.
    $service->markPaid($c);
    expect($c->fresh()->status)->toBe(CommissionStatus::Credited);
});

test('markPaid settles an approved commission', function () {
    $service = app(CommissionService::class);
    $c = Commission::factory()->approved()->create();

    $service->markPaid($c);

    expect($c->fresh()->status)->toBe(CommissionStatus::Paid);
});

test('a staff member cannot be a referrer (segregation of duties)', function () {
    $staff = User::factory()->create(['role' => 'va']);
    $client = User::factory()->create(['role' => 'client']);

    referrals()->attachReferral($client, $staff->referral_code);

    expect($client->fresh()->referred_by)->toBeNull()
        ->and(ReferralRelationship::count())->toBe(0);
});

test('the affiliate page renders for a referrer holding non-AUD commissions', function () {
    $me = User::factory()->create(['role' => 'client']);
    Commission::factory()->create(['referrer_id' => $me->id, 'currency' => 'USD', 'amount' => 5_000, 'status' => CommissionStatus::Credited]);
    $this->actingAs($me);

    $this->get(route('filament.client.pages.affiliate'))->assertOk();
});

// ---------------------------------------------------------------------------
// Filament UI
// ---------------------------------------------------------------------------

test('an admin can approve a commission from the admin resource', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $c = Commission::factory()->create(['status' => CommissionStatus::Pending]);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Commissions\Pages\ListCommissions::class)
        ->callTableAction('approve', $c);

    expect($c->fresh()->status)->toBe(CommissionStatus::Approved);
});

test('a client only sees their own commissions and can take one as credit', function () {
    $me = User::factory()->create(['role' => 'client']);
    $other = User::factory()->create(['role' => 'client']);
    $mine = Commission::factory()->approved()->create(['referrer_id' => $me->id]);
    $theirs = Commission::factory()->approved()->create(['referrer_id' => $other->id]);
    $this->actingAs($me);
    Filament\Facades\Filament::setCurrentPanel('client');

    $component = Livewire\Livewire::test(\App\Filament\Client\Resources\Commissions\Pages\ListCommissions::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);

    $component->callTableAction('applyAsCredit', $mine);
    expect($mine->fresh()->status)->toBe(CommissionStatus::Credited);
});

test('the client affiliate page renders the referral link and earnings', function () {
    $me = User::factory()->create(['role' => 'client']);
    Commission::factory()->credited()->create(['referrer_id' => $me->id, 'amount' => 5_000]);
    $this->actingAs($me);

    $this->get(route('filament.client.pages.affiliate'))
        ->assertOk()
        ->assertSee('?ref='.$me->referral_code, false);
});
