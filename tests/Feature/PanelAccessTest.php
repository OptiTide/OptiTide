<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('clients cannot access the admin panel', function () {
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->get('/admin')->assertForbidden();
});

test('staff can access the admin panel', function (string $role) {
    $staff = User::factory()->create(['role' => $role]);

    $this->actingAs($staff)->get('/admin')->assertOk();
})->with(['admin', 'va']);

test('clients can access the client portal', function () {
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->get('/client')->assertOk();
});

test('clients only see their own orders in the portal', function () {
    $mine = User::factory()->create(['role' => 'client']);
    $other = User::factory()->create(['role' => 'client']);

    $myOrder = Order::create(['user_id' => $mine->id, 'total' => 75_000])->fresh();
    $otherOrder = Order::create(['user_id' => $other->id, 'total' => 150_000])->fresh();

    $this->actingAs($mine)
        ->get('/client/orders')
        ->assertOk()
        ->assertSee($myOrder->order_number)
        ->assertDontSee($otherOrder->order_number);
});

test('client order list masks internal pipeline stage names', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = Order::create(['user_id' => $client->id, 'total' => 75_000]);

    foreach ([\App\Enums\OrderState::AdminReview, \App\Enums\OrderState::GeneratingMockup] as $state) {
        $order->transitionTo($state);
    }

    $this->actingAs($client)
        ->get('/client/orders')
        ->assertOk()
        ->assertSee('Design In Progress')
        ->assertDontSee('AI Mockup Generation');
});

test('users receive a referral code on registration', function () {
    $user = User::factory()->create();

    expect($user->fresh()->referral_code)->toMatch('/^[A-Z0-9]{8}$/');
});

test('virtual assistants cannot manage users', function () {
    $va = User::factory()->create(['role' => 'va']);

    $this->actingAs($va)->get('/admin/users')->assertForbidden();
});

test('admins can manage users', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/users')->assertOk();
});

// ---------------------------------------------------------------------------
// Per-role access to admin-only resources + pages (Epic 14 hardening)
// ---------------------------------------------------------------------------

test('virtual assistants cannot access admin-only resources', function (string $resource) {
    $va = User::factory()->create(['role' => 'va']);
    $this->actingAs($va);
    Filament\Facades\Filament::setCurrentPanel('admin');

    $this->get($resource::getUrl('index'))->assertForbidden();
})->with([
    \App\Filament\Resources\Products\ProductResource::class,
    \App\Filament\Resources\ServerMonitors\ServerMonitorResource::class,
    \App\Filament\Resources\Invoices\InvoiceResource::class,
    \App\Filament\Resources\Contracts\ContractResource::class,
    \App\Filament\Resources\Commissions\CommissionResource::class,
    \App\Filament\Resources\CmsPages\CmsPageResource::class,
]);

test('admins can access those admin-only resources', function (string $resource) {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    Filament\Facades\Filament::setCurrentPanel('admin');

    $this->get($resource::getUrl('index'))->assertOk();
})->with([
    \App\Filament\Resources\Products\ProductResource::class,
    \App\Filament\Resources\ServerMonitors\ServerMonitorResource::class,
    \App\Filament\Resources\Invoices\InvoiceResource::class,
    \App\Filament\Resources\Contracts\ContractResource::class,
    \App\Filament\Resources\Commissions\CommissionResource::class,
    \App\Filament\Resources\CmsPages\CmsPageResource::class,
]);

test('virtual assistants cannot access admin-only pages', function (string $page) {
    $va = User::factory()->create(['role' => 'va']);
    $this->actingAs($va);
    Filament\Facades\Filament::setCurrentPanel('admin');

    $this->get($page::getUrl())->assertForbidden();
})->with([
    // Wrap each in a tuple: a flat 2-element list of class strings is
    // misinterpreted by PHPUnit as a [class, method] data-provider callable.
    [\App\Filament\Pages\ManageAnalytics::class],
    [\App\Filament\Pages\BackupMonitor::class],
]);

test('virtual assistants keep access to VA-appropriate resources', function (string $resource) {
    $va = User::factory()->create(['role' => 'va']);
    $this->actingAs($va);
    Filament\Facades\Filament::setCurrentPanel('admin');

    $this->get($resource::getUrl('index'))->assertOk();
})->with([
    \App\Filament\Resources\Leads\LeadResource::class,
    \App\Filament\Resources\Blogs\BlogResource::class,
    \App\Filament\Resources\Orders\OrderResource::class,
]);

test('only admins may delete an order', function () {
    $va = User::factory()->create(['role' => 'va']);
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $order = Order::create(['user_id' => $client->id, 'total' => 50_000])->fresh();

    expect($va->can('delete', $order))->toBeFalse()
        ->and($admin->can('delete', $order))->toBeTrue()
        // ...but a VA can still work the pipeline (update) and view.
        ->and($va->can('update', $order))->toBeTrue()
        ->and($client->can('view', $order))->toBeTrue();       // client sees own
});

test('an admin cannot bulk-delete their own account (no self-lockout)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $other = User::factory()->create(['role' => 'client']);
    $this->actingAs($admin);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
        ->callTableBulkAction('delete', [$admin, $other]);

    expect(User::find($admin->id))->not->toBeNull()  // self excluded by UserPolicy::delete
        ->and(User::find($other->id))->toBeNull();    // the other user is deleted
});

test('an admin cannot change their own role (no self-demotion)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\EditUser::class, ['record' => $admin->id])
        ->assertFormFieldIsDisabled('role');
});
