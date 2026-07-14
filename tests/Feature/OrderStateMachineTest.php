<?php

use App\Enums\OrderState;
use App\Exceptions\InvalidStateTransition;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrder(): Order
{
    return Order::create([
        'user_id' => User::factory()->create()->id,
        'subtotal' => 250_000,
        'total' => 250_000,
    ]);
}

test('new orders start at pending intake with a generated order number', function () {
    $order = makeOrder()->fresh();

    expect($order->state)->toBe(OrderState::PendingIntake)
        ->and($order->order_number)->toMatch('/^OT-\d{6}$/');
});

test('valid transitions succeed and record an audit trail with the actor', function () {
    $order = makeOrder();
    $admin = User::factory()->create(['role' => 'admin']);

    $order->transitionTo(OrderState::AdminReview, $admin, 'Looks complete');

    expect($order->state)->toBe(OrderState::AdminReview);

    $transition = $order->stateTransitions()->first();
    expect($transition->from_state)->toBe(OrderState::PendingIntake)
        ->and($transition->to_state)->toBe(OrderState::AdminReview)
        ->and($transition->actor_id)->toBe($admin->id)
        ->and($transition->notes)->toBe('Looks complete');
});

test('skipping pipeline stages is rejected', function () {
    makeOrder()->transitionTo(OrderState::Delivered);
})->throws(InvalidStateTransition::class);

test('qa stages can loop back to regeneration', function () {
    $order = makeOrder();

    foreach ([OrderState::AdminReview, OrderState::GeneratingMockup, OrderState::MockupQa] as $state) {
        $order->transitionTo($state);
    }

    // QA rejects the mockup: back to generation for another attempt.
    $order->transitionTo(OrderState::GeneratingMockup);

    expect($order->state)->toBe(OrderState::GeneratingMockup)
        ->and($order->stateTransitions()->count())->toBe(4);
});

test('delivered is a terminal state', function () {
    expect(OrderState::Delivered->allowedTransitions())->toBe([]);
});

test('the full happy path walks all eight stages', function () {
    $order = makeOrder();

    foreach ([
        OrderState::AdminReview,
        OrderState::GeneratingMockup,
        OrderState::MockupQa,
        OrderState::ClientReview,
        OrderState::GeneratingLogic,
        OrderState::FinalQa,
        OrderState::Delivered,
    ] as $state) {
        $order->transitionTo($state);
    }

    expect($order->state)->toBe(OrderState::Delivered)
        ->and($order->stateTransitions()->count())->toBe(7);
});

test('internal ai stages are masked from clients', function () {
    expect(OrderState::GeneratingMockup->isVisibleToClient())->toBeFalse()
        ->and(OrderState::GeneratingMockup->clientFacingLabel())->toBe('Design In Progress')
        ->and(OrderState::FinalQa->clientFacingLabel())->toBe('Development In Progress')
        ->and(OrderState::GeneratingMockup->clientFacingLabel())->not->toContain('AI');
});

test('money columns round-trip through the cast', function () {
    $order = makeOrder()->fresh();

    expect($order->total->amount)->toBe(250_000)
        ->and($order->total->currency)->toBe('AUD')
        ->and($order->total->format())->toBe('$2,500.00');
});

test('assigning money in a different currency than the row is rejected', function () {
    $order = makeOrder()->fresh();

    $order->total = new \App\Support\Money(10_000, 'USD');
})->throws(InvalidArgumentException::class);

test('a stale in-memory state cannot overwrite a concurrent transition', function () {
    $order = makeOrder();
    $stale = Order::find($order->id);

    $order->transitionTo(OrderState::AdminReview);

    // $stale still believes the order is at pending_intake.
    $stale->transitionTo(OrderState::AdminReview);
})->throws(InvalidStateTransition::class);
