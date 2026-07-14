<?php

use App\Enums\MonitorStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\CheckMonitorJob;
use App\Models\HelpdeskTicket;
use App\Models\ServerMonitor;
use App\Models\User;
use App\Services\MonitorService;
use App\Services\Whm\NullWhmClient;
use App\Services\Whm\WhmApiClient;
use App\Services\Whm\WhmClient;
use App\Services\Whm\WhmException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * A MonitorService whose SSL probe is stubbed, so tests don't hit the network
 * for certificate expiry (uptime still goes through the fakeable Http client).
 */
class StubbedSslMonitorService extends MonitorService
{
    public ?Carbon $sslExpiry = null;

    protected function fetchSslExpiry(ServerMonitor $monitor): ?Carbon
    {
        return $this->sslExpiry;
    }
}

function admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

// ---------------------------------------------------------------------------
// Uptime → ticket lifecycle
// ---------------------------------------------------------------------------

test('an up monitor stays up and opens no ticket', function () {
    admin();
    Http::fake(['*' => Http::response('OK', 200)]);
    $monitor = ServerMonitor::factory()->create(['status' => MonitorStatus::Unknown]);

    (new StubbedSslMonitorService(failureThreshold: 2))->check($monitor);

    expect($monitor->fresh()->status)->toBe(MonitorStatus::Up)
        ->and(HelpdeskTicket::count())->toBe(0);
});

test('a single failure does not flip to down (flap debounce)', function () {
    admin();
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = ServerMonitor::factory()->up()->create();

    (new StubbedSslMonitorService(failureThreshold: 2))->check($monitor);

    $fresh = $monitor->fresh();
    expect($fresh->status)->toBe(MonitorStatus::Up)          // still up until threshold
        ->and($fresh->consecutive_failures)->toBe(1)
        ->and(HelpdeskTicket::count())->toBe(0);
});

test('crossing the failure threshold flags down and opens one urgent ticket', function () {
    admin();
    Http::fake(['*' => Http::response('', 503)]);
    $monitor = ServerMonitor::factory()->up()->create();
    $service = new StubbedSslMonitorService(failureThreshold: 2);

    $service->check($monitor); // failure 1
    $service->check($monitor); // failure 2 → down + ticket

    $fresh = $monitor->fresh();
    expect($fresh->status)->toBe(MonitorStatus::Down)
        ->and($fresh->incident_ticket_id)->not->toBeNull();

    $ticket = HelpdeskTicket::sole();
    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::Urgent)
        ->and($ticket->messages()->first()->is_internal)->toBeTrue();
});

test('repeated down checks never open a second ticket (idempotent)', function () {
    admin();
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = ServerMonitor::factory()->down()->create(['consecutive_failures' => 2]);
    $service = new StubbedSslMonitorService(failureThreshold: 2);

    $service->check($monitor);
    $service->check($monitor);
    $service->check($monitor);

    expect(HelpdeskTicket::count())->toBe(1);
});

test('recovery resolves the incident ticket and releases the guard', function () {
    admin();
    $monitor = ServerMonitor::factory()->up()->create();
    $service = new StubbedSslMonitorService(failureThreshold: 1);

    // First poll fails (down + ticket), second succeeds (recovery).
    Http::fakeSequence()->push('', 500)->push('OK', 200);

    $service->check($monitor); // down + ticket
    $ticketId = $monitor->fresh()->incident_ticket_id;
    expect($ticketId)->not->toBeNull();

    $service->check($monitor); // recovery

    $fresh = $monitor->fresh();
    expect($fresh->status)->toBe(MonitorStatus::Up)
        ->and($fresh->incident_ticket_id)->toBeNull()
        ->and(HelpdeskTicket::find($ticketId)->status)->toBe(TicketStatus::Resolved);
});

// ---------------------------------------------------------------------------
// SSL expiry → ticket lifecycle
// ---------------------------------------------------------------------------

test('a soon-to-expire certificate opens a high-priority SSL ticket, renewal resolves it', function () {
    admin();
    Http::fake(['*' => Http::response('OK', 200)]);
    $monitor = ServerMonitor::factory()->up()->create();
    $service = new StubbedSslMonitorService(failureThreshold: 2, sslExpiryDays: 14);

    // Expires in 3 days → within threshold.
    $service->sslExpiry = now()->addDays(3);
    $service->check($monitor);

    $sslTicketId = $monitor->fresh()->ssl_ticket_id;
    expect($sslTicketId)->not->toBeNull()
        ->and(HelpdeskTicket::find($sslTicketId)->priority)->toBe(TicketPriority::High);

    // Renewed → far future, clears the guard and resolves the ticket.
    $service->sslExpiry = now()->addDays(90);
    $service->check($monitor);

    expect($monitor->fresh()->ssl_ticket_id)->toBeNull()
        ->and(HelpdeskTicket::find($sslTicketId)->status)->toBe(TicketStatus::Resolved);
});

test('an unknown SSL expiry never false-resolves an open SSL ticket', function () {
    admin();
    Http::fake(['*' => Http::response('OK', 200)]);
    $monitor = ServerMonitor::factory()->up()->create();
    $service = new StubbedSslMonitorService(sslExpiryDays: 14);

    $service->sslExpiry = now()->addDays(2);
    $service->check($monitor);
    $sslTicketId = $monitor->fresh()->ssl_ticket_id;

    $service->sslExpiry = null; // handshake failed this round
    $service->check($monitor);

    expect($monitor->fresh()->ssl_ticket_id)->toBe($sslTicketId)
        ->and(HelpdeskTicket::find($sslTicketId)->status)->toBe(TicketStatus::Open);
});

// ---------------------------------------------------------------------------
// WHM client — config-gated + fail-closed
// ---------------------------------------------------------------------------

test('the WHM client fails closed without credentials', function () {
    config()->set('services.whm', ['host' => null, 'username' => null, 'api_token' => null]);
    $this->app->forgetInstance(WhmClient::class);

    $whm = app(WhmClient::class);

    expect($whm)->toBeInstanceOf(NullWhmClient::class)
        ->and($whm->isConfigured())->toBeFalse();

    $whm->serverStatus();
})->throws(WhmException::class);

test('a real WHM driver is bound when credentials are present', function () {
    config()->set('services.whm', [
        'host' => 'server.example.com',
        'port' => 2087,
        'username' => 'root',
        'api_token' => 'secret-token',
    ]);
    $this->app->forgetInstance(WhmClient::class);

    $whm = app(WhmClient::class);

    expect($whm)->toBeInstanceOf(WhmApiClient::class)
        ->and($whm->isConfigured())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Command + jobs
// ---------------------------------------------------------------------------

test('monitors:check dispatches a check job for each active monitor only', function () {
    Queue::fake();
    ServerMonitor::factory()->count(2)->create(['is_active' => true]);
    ServerMonitor::factory()->create(['is_active' => false]);

    $this->artisan('monitors:check')->assertSuccessful();

    Queue::assertPushed(CheckMonitorJob::class, 2);
});

test('the check job skips an inactive monitor', function () {
    admin();
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = ServerMonitor::factory()->create(['is_active' => false, 'status' => MonitorStatus::Up]);

    (new CheckMonitorJob($monitor->id))->handle(new StubbedSslMonitorService(failureThreshold: 1));

    expect($monitor->fresh()->status)->toBe(MonitorStatus::Up)
        ->and(HelpdeskTicket::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Filament admin UI
// ---------------------------------------------------------------------------

test('the server monitors resource lists records and check-now runs a check', function () {
    admin();
    Http::fake(['*' => Http::response('OK', 200)]);
    // Bind the SSL-stubbed service so the record action does not open a real
    // outbound TLS socket to the random factory domain.
    $this->app->bind(MonitorService::class, fn () => new StubbedSslMonitorService);
    $monitor = ServerMonitor::factory()->create(['status' => MonitorStatus::Unknown]);
    $this->actingAs(admin());
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\ServerMonitors\Pages\ListServerMonitors::class)
        ->assertCanSeeTableRecords([$monitor])
        ->callTableAction('checkNow', $monitor);

    expect($monitor->fresh()->status)->toBe(MonitorStatus::Up);
});

test('the backup monitor page renders and reports no backups yet', function () {
    $this->actingAs(admin());
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Pages\BackupMonitor::class)
        ->assertOk()
        ->assertSet('healthy', false)
        ->assertSet('whmConfigured', false);
});

test('the backup monitor page lists archives on the configured disk and reports healthy', function () {
    config()->set('backup.backup.name', 'OptiTide');
    config()->set('backup.backup.destination.disks', ['local']);
    Illuminate\Support\Facades\Storage::fake('local');
    // A recent, small archive under the {name}/ folder spatie writes to.
    Illuminate\Support\Facades\Storage::disk('local')->put('OptiTide/optitide-2026-07-13-01-30-00.zip', str_repeat('x', 1024));

    $this->actingAs(admin());
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Pages\BackupMonitor::class)
        ->assertOk()
        ->assertSet('healthy', true)
        ->assertSet('diskError', null)
        ->assertCount('backups', 1);
});
