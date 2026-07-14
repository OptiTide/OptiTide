<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Cashier's webhook controller (which keeps subscriptions in sync)
 * with one-time payment handling for cart checkouts.
 */
class StripeWebhookController extends CashierWebhookController
{
    public function __construct()
    {
        // Cashier only attaches its VerifyWebhookSignature middleware when a
        // signing secret is configured. Without one, every payload would be
        // trusted unauthenticated — so fail closed rather than fail open.
        // Tests exercise the handlers directly without a secret.
        if (blank(config('cashier.webhook.secret')) && ! app()->runningUnitTests()) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Stripe webhook secret is not configured.');
        }

        parent::__construct();
    }

    public function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'] ?? [];

        // Subscription-mode sessions are handled by Cashier's own
        // customer.subscription.* events.
        if (($session['mode'] ?? null) !== 'payment') {
            return $this->successMethod();
        }

        // Async payment methods (e.g. BECS Direct Debit) complete the session
        // before funds settle; wait for the async_payment_succeeded event.
        if (($session['payment_status'] ?? null) !== 'paid') {
            return $this->successMethod();
        }

        $this->fulfillPaidSession($session);

        return $this->successMethod();
    }

    /** Fires for delayed-settlement methods once the funds clear. */
    public function handleCheckoutSessionAsyncPaymentSucceeded(array $payload): Response
    {
        $session = $payload['data']['object'] ?? [];

        if (($session['mode'] ?? null) === 'payment') {
            $this->fulfillPaidSession($session);
        }

        return $this->successMethod();
    }

    protected function fulfillPaidSession(array $session): void
    {
        $order = $this->resolveOrder($session);

        // Unknown session or already processed (webhooks may be delivered
        // more than once) — nothing to do.
        if ($order === null || $order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order, $session) {
            // Compare-and-swap: only the delivery that flips the row from
            // pending gets to issue the invoice, so concurrent redelivery
            // cannot duplicate it.
            $claimed = Order::whereKey($order->getKey())
                ->where('payment_status', PaymentStatus::Pending->value)
                ->update([
                    'payment_status' => PaymentStatus::Paid->value,
                    'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                    'placed_at' => $order->placed_at ?? now(),
                    'updated_at' => now(),
                ]);

            if ($claimed === 0) {
                return;
            }

            $order->refresh();

            // The order total is GST-inclusive (what Stripe charged); record the
            // GST component so the paid invoice is a compliant tax invoice.
            $paidTotal = $order->total->amount;
            $gst = InvoiceService::gstComponent($paidTotal);

            $invoice = $order->invoices()->create([
                'user_id' => $order->user_id,
                'status' => InvoiceStatus::Paid,
                'currency' => $order->currency,
                'subtotal' => $paidTotal - $gst,
                'tax' => $gst,
                'total' => $paidTotal,
                'amount_paid' => $paidTotal,
                'due_date' => today(),
                'paid_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price->amount,
                    'total' => $item->total->amount,
                    'currency' => $item->currency,
                ]);
            }

            // Bespoke builds require a signed service agreement before
            // onboarding; issue it as soon as payment lands.
            app(\App\Services\ContractService::class)->createForOrder($order);

            // Affiliate commission on the referred client's first paid order
            // (idempotent — inside this once-only block).
            app(\App\Services\ReferralService::class)->recordCommissionForFirstPaidOrder($order);
        });
    }

    protected function resolveOrder(array $session): ?Order
    {
        $bySession = Order::where('stripe_checkout_session_id', $session['id'] ?? '')->first();

        if ($bySession !== null) {
            return $bySession;
        }

        // Fallback for the race where the webhook beats our session-id save:
        // only match an order that has NOT yet been bound to any session, so
        // a forged metadata order_id can't hijack an unrelated order.
        return Order::whereNull('stripe_checkout_session_id')
            ->whereKey($session['metadata']['order_id'] ?? null)
            ->first();
    }
}
