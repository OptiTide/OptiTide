<?php

use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductSeeder::class);
});

test('the home page renders the catalog highlights', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('OptiTide')
        ->assertSee('Standard Website')
        ->assertSee('$750.00')
        ->assertSee('Managed Hosting')
        ->assertSee('Australian Consumer Law');
});

test('the services page lists every active product grouped by category', function () {
    $this->get('/services')
        ->assertOk()
        ->assertSeeInOrder(['Web Development', 'SEO', 'Social Media Management', 'Web Hosting'])
        ->assertSee('Tier 4 SEO')
        ->assertSee('$2,500.00');
});

test('a service detail page is reachable by slug', function () {
    $this->get('/services/custom-website')
        ->assertOk()
        ->assertSee('Custom Website')
        ->assertSee('Add to cart');
});

test('inactive products 404 on the storefront', function () {
    Product::where('slug', 'custom-website')->update(['is_active' => false]);

    $this->get('/services/custom-website')->assertNotFound();
});

test('one-time products can be added to and removed from the cart', function () {
    $product = Product::where('slug', 'pro-website')->first();

    $this->post("/cart/{$product->id}")->assertRedirect('/cart');

    $this->get('/cart')
        ->assertOk()
        ->assertSee('Pro Website')
        ->assertSee('$1,500.00');

    $this->delete("/cart/{$product->id}")->assertRedirect('/cart');

    $this->get('/cart')->assertSee('Your cart is empty');
});

test('subscription products cannot be added to the cart', function () {
    $hosting = Product::where('slug', 'managed-hosting')->first();

    $this->post("/cart/{$hosting->id}")
        ->assertRedirect("/services/{$hosting->slug}")
        ->assertSessionHas('error');

    $this->get('/cart')->assertSee('Your cart is empty');
});

test('guests are sent to the client login when checking out', function () {
    $product = Product::where('slug', 'base-seo')->first();
    $this->post("/cart/{$product->id}");

    $this->post('/checkout')->assertRedirect(route('filament.client.auth.login'));
});

test('checkout with an empty cart returns to the cart page', function () {
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->post('/checkout')->assertRedirect('/cart');
});

test('checkout without stripe configured fails gracefully and keeps the order unsaved', function () {
    config(['cashier.secret' => null]);

    $client = User::factory()->create(['role' => 'client']);
    $product = Product::where('slug', 'base-seo')->first();

    $this->actingAs($client)->post("/cart/{$product->id}");
    $this->actingAs($client)->from('/cart')->post('/checkout')
        ->assertRedirect('/cart')
        ->assertSessionHas('error');

    expect(\App\Models\Order::count())->toBe(0);
});

test('the contact form creates a lead', function () {
    $this->post('/contact', [
        'name' => 'Jane Business',
        'email' => 'jane@example.com',
        'company' => 'Jane Pty Ltd',
        'message' => 'I need a new website for my cafe.',
    ])->assertRedirect('/contact')->assertSessionHas('success');

    $lead = Lead::first();
    expect($lead)->not->toBeNull()
        ->and($lead->email)->toBe('jane@example.com')
        ->and($lead->source)->toBe('contact_form');
});

test('bots that fill the honeypot get a fake success and create no lead', function () {
    $this->post('/contact', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'message' => 'Buy cheap widgets now online.',
        'company_website' => 'https://spam.example',
    ])->assertRedirect('/contact')->assertSessionHas('success');

    expect(Lead::count())->toBe(0);
});

test('the checkout success page is scoped to the ordering user', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $other = User::factory()->create(['role' => 'client']);

    $order = \App\Models\Order::create(['user_id' => $owner->id, 'currency' => 'AUD', 'subtotal' => 150_000, 'total' => 150_000]);
    $order->forceFill(['stripe_checkout_session_id' => 'cs_test_owner'])->save();

    // A different user cannot confirm someone else's session.
    $this->actingAs($other)->get('/checkout/success?session_id=cs_test_owner')
        ->assertRedirect(route('home'));
});

test('the checkout success page ignores a blank session id', function () {
    $client = User::factory()->create(['role' => 'client']);

    // A null-session order (e.g. a manual CRM order) must never be matched.
    \App\Models\Order::create(['user_id' => $client->id, 'currency' => 'AUD', 'subtotal' => 75_000, 'total' => 75_000]);

    $this->actingAs($client)->get('/checkout/success')->assertRedirect(route('home'));
});

test('a one-time product cannot be subscribed to', function () {
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->post('/subscribe/pro-website')->assertNotFound();
});

test('subscribing to a deactivated hosting plan 404s', function () {
    Product::where('slug', 'managed-hosting')->update(['is_active' => false]);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->post('/subscribe/managed-hosting')->assertNotFound();
});

test('subscription checkout without stripe configured fails gracefully', function () {
    config(['cashier.secret' => null]);
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->from('/services/managed-hosting')->post('/subscribe/managed-hosting')
        ->assertRedirect('/services/managed-hosting')
        ->assertSessionHas('error');
});

test('subscription checkout without a stripe price id fails gracefully', function () {
    config(['cashier.secret' => 'sk_test_fake']);
    // Seeded hosting plans have no stripe_price_id yet.
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->from('/services/managed-hosting')->post('/subscribe/managed-hosting')
        ->assertRedirect('/services/managed-hosting')
        ->assertSessionHas('error');
});

test('a product deactivated after being carted is silently dropped and checkout aborts', function () {
    $product = Product::where('slug', 'pro-website')->first();
    $client = User::factory()->create(['role' => 'client']);

    $this->actingAs($client)->post("/cart/{$product->id}");

    $product->update(['is_active' => false]);

    $this->actingAs($client)->get('/cart')->assertOk()->assertSee('Your cart is empty');
    $this->actingAs($client)->post('/checkout')->assertRedirect('/cart');

    expect(\App\Models\Order::count())->toBe(0);
});

test('cart prices track the live product price, not an add-time snapshot', function () {
    $product = Product::where('slug', 'pro-website')->first();

    $cart = app(\App\Services\Cart::class);
    $cart->add($product);

    $product->update(['price' => 99_900]);

    expect($cart->subtotal()->amount)->toBe(99_900)
        ->and($cart->lines()->first()['total']->amount)->toBe(99_900);
});

test('the storefront emits Australia geotargeting signals and Organization JSON-LD', function () {
    config()->set('company.abn', '12 345 678 901');
    config()->set('company.legal_name', 'OptiTide Pty Ltd');

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="en-AU"', false)
        ->assertSee('hreflang="en-au"', false)
        ->assertSee('<meta name="geo.region" content="AU">', false)
        ->assertSee('og:locale', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('"areaServed":"AU"', false)
        ->assertSee('"addressCountry":"AU"', false)
        ->assertSee('12 345 678 901', false); // ABN in the Organization identifier
});
