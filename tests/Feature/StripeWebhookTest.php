<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paidCheckoutPayload(Order $order, string $sessionId = 'cs_test_abc123'): array
{
    return [
        'id' => 'evt_test_1',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'mode' => 'payment',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_123',
                'metadata' => ['order_id' => (string) $order->id],
            ],
        ],
    ];
}

function pendingOrder(): Order
{
    $client = User::factory()->create(['role' => 'client']);

    $order = Order::create([
        'user_id' => $client->id,
        'subtotal' => 150_000,
        'total' => 150_000,
    ]);

    $order->items()->create([
        'description' => 'Pro Website',
        'quantity' => 1,
        'unit_price' => 150_000,
        'total' => 150_000,
        'currency' => 'AUD',
    ]);

    $order->forceFill(['stripe_checkout_session_id' => 'cs_test_abc123'])->save();

    return $order;
}

test('a completed checkout session marks the order paid and issues a paid invoice', function () {
    $order = pendingOrder();

    $this->postJson('/stripe/webhook', paidCheckoutPayload($order))->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->stripe_payment_intent_id)->toBe('pi_test_123')
        ->and($order->placed_at)->not->toBeNull();

    $invoice = $order->invoices()->first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->total->amount)->toBe(150_000)
        ->and($invoice->amount_paid->amount)->toBe(150_000)
        ->and($invoice->items()->count())->toBe(1);
});

test('webhook redelivery is idempotent', function () {
    $order = pendingOrder();

    $this->postJson('/stripe/webhook', paidCheckoutPayload($order))->assertOk();
    $this->postJson('/stripe/webhook', paidCheckoutPayload($order))->assertOk();

    expect($order->invoices()->count())->toBe(1);
});

test('unknown checkout sessions are acknowledged without side effects', function () {
    $this->postJson('/stripe/webhook', [
        'id' => 'evt_test_2',
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => 'cs_unknown', 'mode' => 'payment', 'metadata' => []]],
    ])->assertOk();

    expect(Order::count())->toBe(0);
});

test('subscription-mode sessions are left to cashier event handling', function () {
    $order = pendingOrder();
    $payload = paidCheckoutPayload($order);
    $payload['data']['object']['mode'] = 'subscription';

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('a session that is not yet paid does not mark the order paid', function () {
    $order = pendingOrder();
    $payload = paidCheckoutPayload($order);
    $payload['data']['object']['payment_status'] = 'unpaid';

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->invoices()->count())->toBe(0);
});

test('an async payment success settles the order', function () {
    $order = pendingOrder();
    $payload = paidCheckoutPayload($order);
    $payload['type'] = 'checkout.session.async_payment_succeeded';

    $this->postJson('/stripe/webhook', $payload)->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->invoices()->count())->toBe(1);
});

test('the webhook finds the order via metadata when the session id was not yet saved', function () {
    $order = pendingOrder();
    $order->forceFill(['stripe_checkout_session_id' => null])->save();

    $this->postJson('/stripe/webhook', paidCheckoutPayload($order, 'cs_not_yet_saved'))->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->invoices()->count())->toBe(1);
});

test('a metadata order_id cannot hijack an order already bound to another session', function () {
    $order = pendingOrder(); // bound to cs_test_abc123

    // Forged payload references a different session but the victim order id.
    $this->postJson('/stripe/webhook', paidCheckoutPayload($order, 'cs_attacker'))->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->invoices()->count())->toBe(0);
});

test('unsigned webhooks are rejected once a signing secret is configured', function () {
    config(['cashier.webhook.secret' => 'whsec_test']);
    $order = pendingOrder();

    $this->postJson('/stripe/webhook', paidCheckoutPayload($order))->assertForbidden();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->invoices()->count())->toBe(0);
});

test('the stripe webhook route is exempt from CSRF verification', function () {
    // Resolving the HTTP kernel runs bootstrap/app.php's withMiddleware
    // callback, which populates the CSRF middleware's except list.
    $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

    expect(app(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)->getExcludedPaths())
        ->toContain('stripe/webhook');
});
