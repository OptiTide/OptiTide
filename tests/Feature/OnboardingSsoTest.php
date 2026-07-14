<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Support\SocialiteUserCreator;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Http\Middleware\PanelFromUrlQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteOauthUser;

uses(RefreshDatabase::class);

function oauthUser(array $overrides = []): SocialiteOauthUser
{
    return (new SocialiteOauthUser)->map(array_merge([
        'id' => '77777',
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
        'nickname' => 'jane',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// SSO user creation (the custom createUserUsing)
// ---------------------------------------------------------------------------

test('SocialiteUserCreator makes a verified, passwordless client and attaches the referral', function () {
    $referrer = User::factory()->create(['role' => 'client']);
    request()->cookies->set('referral', $referrer->referral_code);

    $user = app(SocialiteUserCreator::class)('google', oauthUser(), FilamentSocialitePlugin::make());

    expect($user->email)->toBe('jane@example.com')
        ->and($user->password)->toBeNull()                 // OAuth-only account
        ->and($user->email_verified_at)->not->toBeNull()   // provider-verified
        ->and($user->role)->toBe(UserRole::Client)         // DB default
        ->and($user->referral_code)->not->toBeNull()       // User::created still fires
        ->and($user->referred_by)->toBe($referrer->id);    // referral replicated
});

test('SocialiteUserCreator falls back to the email local-part when the provider gives no name', function () {
    $user = app(SocialiteUserCreator::class)('github', oauthUser(['name' => null, 'nickname' => null]), FilamentSocialitePlugin::make());

    expect($user->name)->toBe('jane');
});

test('the OAuth callback registers a new client, links the provider, and signs them in', function () {
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn(oauthUser());
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $state = PanelFromUrlQuery::encrypt('client');
    $this->get("/client/oauth/callback/google?state={$state}&code=xyz");

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->toBeNull()
        ->and(DB::table('socialite_users')->where('user_id', $user->id)->where('provider', 'google')->exists())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('SSO refuses to authenticate a staff account (staff stay password-only)', function () {
    // A staff member's email must never authenticate via client-panel OAuth —
    // both panels share the web guard, so it would grant /admin access.
    User::factory()->create(['role' => 'admin', 'email' => 'boss@optitide.io']);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn(oauthUser(['email' => 'boss@optitide.io', 'id' => 'staff-oauth']));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $state = PanelFromUrlQuery::encrypt('client');
    $this->get("/client/oauth/callback/google?state={$state}&code=x");

    $this->assertGuest();                                              // never logged in
    expect(DB::table('socialite_users')->count())->toBe(0);           // never linked
});

test('a second login for the same provider identity re-uses the existing user (no duplicate)', function () {
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('user')->andReturn(oauthUser());
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $state = PanelFromUrlQuery::encrypt('client');
    $this->get("/client/oauth/callback/google?state={$state}&code=1");
    auth()->logout();
    $this->get("/client/oauth/callback/google?state={$state}&code=2");

    expect(User::where('email', 'jane@example.com')->count())->toBe(1)
        ->and(DB::table('socialite_users')->where('provider', 'google')->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Config-gated provider buttons
// ---------------------------------------------------------------------------

test('social login buttons are hidden without credentials and shown once configured', function () {
    // Default test env has no OAuth creds → no buttons.
    $this->get('/client/login')
        ->assertOk()
        ->assertDontSee('Continue with Google')
        ->assertDontSee('Continue with GitHub');

    config()->set('services.google.client_id', 'test-client-id');

    $this->get('/client/login')
        ->assertOk()
        ->assertSee('Continue with Google')
        ->assertDontSee('Continue with GitHub'); // github still unconfigured
});

// ---------------------------------------------------------------------------
// Forced onboarding wizard
// ---------------------------------------------------------------------------

test('an un-onboarded client is forced to the onboarding wizard', function () {
    $client = User::factory()->create(['role' => 'client', 'onboarded_at' => null]);

    $this->actingAs($client)->get('/client')->assertRedirect('/client/onboarding');
});

test('the onboarding wizard page itself is reachable while onboarding (no redirect loop)', function () {
    $client = User::factory()->create(['role' => 'client', 'onboarded_at' => null]);

    $this->actingAs($client)->get('/client/onboarding')->assertOk();
});

test('an onboarded client reaches the dashboard normally', function () {
    $client = User::factory()->create(['role' => 'client', 'onboarded_at' => now()]);

    $this->actingAs($client)->get('/client')->assertOk();
});

test('staff visiting the client panel are exempt from onboarding', function () {
    $staff = User::factory()->create(['role' => 'va', 'onboarded_at' => null]);

    $this->actingAs($staff)->get('/client')->assertOk();
});

test('completing the wizard stamps onboarded_at and releases the guard', function () {
    $client = User::factory()->create(['role' => 'client', 'onboarded_at' => null]);
    $this->actingAs($client);
    Filament\Facades\Filament::setCurrentPanel('client');

    Livewire\Livewire::test(\App\Filament\Client\Pages\Onboarding::class)
        ->set('company_name', 'Acme Pty Ltd')
        ->set('phone', '0400 000 000')
        ->call('save')
        ->assertHasNoErrors();

    $client->refresh();
    expect($client->hasCompletedOnboarding())->toBeTrue()
        ->and($client->company_name)->toBe('Acme Pty Ltd')
        ->and($client->phone)->toBe('0400 000 000');

    // Guard released — the dashboard is now reachable.
    $this->actingAs($client)->get('/client')->assertOk();
});

test('the wizard requires a company name', function () {
    $client = User::factory()->create(['role' => 'client', 'onboarded_at' => null]);
    $this->actingAs($client);
    Filament\Facades\Filament::setCurrentPanel('client');

    Livewire\Livewire::test(\App\Filament\Client\Pages\Onboarding::class)
        ->set('company_name', '')
        ->call('save')
        ->assertHasErrors(['company_name' => 'required']);

    expect($client->fresh()->hasCompletedOnboarding())->toBeFalse();
});
