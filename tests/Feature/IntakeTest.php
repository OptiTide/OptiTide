<?php

use App\Enums\OrderState;
use App\Models\ClientSubmission;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\FormSchemaSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductSeeder::class);
    $this->seed(FormSchemaSeeder::class);
    Storage::fake('local');
});

/** A paid order for the given product, sitting at intake. */
function intakeOrder(string $slug, User $client): Order
{
    $product = Product::where('slug', $slug)->first();

    $order = Order::create([
        'user_id' => $client->id,
        'payment_status' => 'paid',
        'subtotal' => $product->price->amount,
        'total' => $product->price->amount,
        'placed_at' => now(),
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'description' => $product->name,
        'quantity' => 1,
        'unit_price' => $product->price->amount,
        'total' => $product->price->amount,
        'currency' => 'AUD',
    ]);

    return $order;
}

/** Minimal valid answers for the Standard Website (basic) form. */
function basicBriefPayload(): array
{
    return [
        'business_name' => 'Acme Widgets',
        'business_description' => 'We sell premium widgets to businesses.',
        'industry' => 'Manufacturing',
        'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        'primary_color' => '#0284c7',
        'secondary_color' => '#f59e0b',
    ];
}

test('needsIntake is true for a paid intake-stage order with a form and no submission', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    expect($order->needsIntake())->toBeTrue()
        ->and($order->onboardingFormSchema()?->key)->toBe('basic_onboarding');
});

test('an unpaid order never needs intake, even at pending_intake', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);
    $order->forceFill(['payment_status' => 'pending'])->save();

    expect($order->fresh()->needsIntake())->toBeFalse();

    // And the brief endpoints are closed to an unpaid order.
    $this->actingAs($client)->get(route('brief.show', $order))
        ->assertRedirect(route('filament.client.resources.orders.index'));
    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload())
        ->assertRedirect(route('filament.client.resources.orders.index'));
    expect(ClientSubmission::count())->toBe(0);
});

test('a brief re-opens when a VA bounces the order back to intake', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload());
    expect($order->refresh()->needsIntake())->toBeFalse(); // now at admin_review

    // VA sends it back for a revised brief.
    $order->transitionTo(OrderState::PendingIntake);

    expect($order->fresh()->needsIntake())->toBeTrue();
    $this->actingAs($client)->get(route('brief.show', $order))->assertOk();
});

test('needsIntake is false when an unsigned agreement blocks it', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('custom-website', $client);
    Contract::create(['user_id' => $client->id, 'order_id' => $order->id, 'title' => 'Agreement']);

    expect($order->needsIntake())->toBeFalse();
});

test('an order with no onboarding product needs no intake', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('base-seo', $client); // SEO has no onboarding_form_key

    expect($order->needsIntake())->toBeFalse()
        ->and($order->onboardingFormSchema())->toBeNull();
});

test('the brief page renders the schema fields for the owner', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)->get(route('brief.show', $order))
        ->assertOk()
        ->assertSee('Standard Website Onboarding')
        ->assertSee('Business name')
        ->assertSee('Upload your logo (high resolution)')
        ->assertSee('Primary brand color');
});

test('another client cannot view or submit a brief', function () {
    $client = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($intruder)->get(route('brief.show', $order))->assertForbidden();
    $this->actingAs($intruder)->post(route('brief.store', $order), basicBriefPayload())->assertForbidden();
});

test('submitting the brief stores data + assets and advances the order', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)
        ->post(route('brief.store', $order), basicBriefPayload())
        ->assertRedirect(route('filament.client.resources.orders.index'))
        ->assertSessionHas('success');

    $order->refresh();
    expect($order->state)->toBe(OrderState::AdminReview);

    $submission = ClientSubmission::first();
    expect($submission)->not->toBeNull()
        ->and($submission->order_id)->toBe($order->id)
        // Text answers go to data; files + colours go to brand_assets.
        ->and($submission->data['business_name'])->toBe('Acme Widgets')
        ->and($submission->data)->not->toHaveKey('logo')
        ->and($submission->brand_assets['primary_color'])->toBe('#0284c7')
        ->and($submission->brand_assets['logo'])->toBeString();

    // The uploaded logo is on the private disk.
    Storage::disk('local')->assertExists($submission->brand_assets['logo']);
});

test('required fields are validated', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)
        ->post(route('brief.store', $order), ['industry' => 'X']) // missing required fields
        ->assertSessionHasErrors(['business_name', 'business_description', 'logo', 'primary_color']);

    expect($order->refresh()->state)->toBe(OrderState::PendingIntake)
        ->and(ClientSubmission::count())->toBe(0);
});

test('an invalid hex colour is rejected', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)
        ->post(route('brief.store', $order), [...basicBriefPayload(), 'primary_color' => 'blue'])
        ->assertSessionHasErrors('primary_color');
});

test('the brief cannot be submitted twice', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload());

    // Order has moved on and a submission exists → gate redirects with an error.
    $this->actingAs($client)->get(route('brief.show', $order))
        ->assertRedirect(route('filament.client.resources.orders.index'))
        ->assertSessionHas('error');

    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload())
        ->assertRedirect(route('filament.client.resources.orders.index'));

    expect(ClientSubmission::count())->toBe(1);
});

test('staff can download a submitted brand asset but outsiders cannot', function () {
    $client = User::factory()->create(['role' => 'client']);
    $va = User::factory()->create(['role' => 'va']);
    $intruder = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload());
    $logoPath = ClientSubmission::first()->brand_assets['logo'];

    $this->actingAs($va)->get(route('brief.asset', ['order' => $order, 'path' => $logoPath]))->assertOk();
    $this->actingAs($client)->get(route('brief.asset', ['order' => $order, 'path' => $logoPath]))->assertOk();
    $this->actingAs($intruder)->get(route('brief.asset', ['order' => $order, 'path' => $logoPath]))->assertForbidden();
});

test('the exhaustive brief accepts a comma-containing select option', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('custom-website', $client);
    // Custom Website requires a signed agreement before the brief opens.
    Contract::create(['user_id' => $client->id, 'order_id' => $order->id, 'title' => 'Agreement'])->markSigned();

    expect($order->fresh()->needsIntake())->toBeTrue()
        ->and($order->onboardingFormSchema()->key)->toBe('exhaustive_onboarding');

    $this->actingAs($client)
        ->post(route('brief.store', $order), [
            ...basicBriefPayload(),
            'pages_needed' => 'Home, About, Shop',
            'target_audience' => 'Cafe owners across Australia.',
            'brand_voice' => 'Professional',
            'must_have_features' => 'Online ordering and bookings.',
            // Two of the three options contain a comma — the string in: rule
            // used to reject them, blocking the whole brief.
            'content_ready' => 'Yes, all ready',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('filament.client.resources.orders.index'));

    expect($order->refresh()->state)->toBe(OrderState::AdminReview);
});

test('multiple-file fields store every uploaded file', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('pro-website', $client); // intermediate schema has multi-file fields

    $this->actingAs($client)
        ->post(route('brief.store', $order), [
            ...basicBriefPayload(),
            'pages_needed' => 'Home, About',
            'brand_imagery' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('filament.client.resources.orders.index'));

    $assets = ClientSubmission::first()->brand_assets['brand_imagery'];
    expect($assets)->toBeArray()->toHaveCount(2);
    foreach ($assets as $path) {
        Storage::disk('local')->assertExists($path);
    }
});

test('a multi-file upload rejects a file over the per-file size cap', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('pro-website', $client);

    $this->actingAs($client)
        ->post(route('brief.store', $order), [
            ...basicBriefPayload(),
            'pages_needed' => 'Home, About',
            'brand_imagery' => [UploadedFile::fake()->create('big.jpg', 20000)], // 20MB > 10240KB cap
        ])
        ->assertSessionHasErrors('brand_imagery.0');

    expect(ClientSubmission::count())->toBe(0);
});

test('a brand-asset download is served as an attachment (SVG stored-XSS guard)', function () {
    $client = User::factory()->create(['role' => 'client']);
    $va = User::factory()->create(['role' => 'va']);
    $order = intakeOrder('standard-website', $client);

    $this->actingAs($client)->post(route('brief.store', $order), [
        ...basicBriefPayload(),
        'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    ]);
    $logoPath = ClientSubmission::first()->brand_assets['logo'];

    // Content-Disposition: attachment stops an SVG rendering inline in-origin.
    $this->actingAs($va)
        ->get(route('brief.asset', ['order' => $order, 'path' => $logoPath]))
        ->assertDownload();
});

test('the asset route refuses a path not recorded in the submission', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = intakeOrder('standard-website', $client);
    $this->actingAs($client)->post(route('brief.store', $order), basicBriefPayload());

    // Path traversal / arbitrary file access attempt.
    $this->actingAs($client)
        ->get(route('brief.asset', ['order' => $order, 'path' => '../../.env']))
        ->assertNotFound();
});
