<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Cart;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Cashier;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(protected Cart $cart) {}

    /**
     * Create a pending order from the cart and hand the customer to Stripe
     * Checkout. The webhook marks the order paid; the cart survives until
     * the success page so a cancelled payment loses nothing.
     */
    public function store(Request $request): RedirectResponse
    {
        // Single snapshot of the cart so the order rows, the stored total,
        // and the Stripe line items are all derived from one read.
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index');
        }

        if (blank(config('cashier.secret'))) {
            return back()->with('error', 'Online payment is not available yet — please contact us to complete your order.');
        }

        $user = $request->user();
        $subtotal = $lines->reduce(
            fn (Money $carry, array $line) => $carry->add($line['total']),
            Money::zero(),
        );

        $order = DB::transaction(function () use ($user, $lines, $subtotal) {
            $order = Order::create([
                'user_id' => $user->id,
                'currency' => 'AUD',
                'subtotal' => $subtotal->amount,
                'total' => $subtotal->amount,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'description' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['product']->price->amount,
                    'total' => $line['total']->amount,
                    'currency' => $line['product']->currency,
                ]);
            }

            return $order;
        });

        try {
            $sessionData = [
                'mode' => 'payment',
                'customer' => $user->createOrGetStripeCustomer()->id,
                'line_items' => $lines->map(fn (array $line) => [
                    'price_data' => [
                        'currency' => strtolower($line['product']->currency),
                        'product_data' => ['name' => $line['product']->name],
                        'unit_amount' => $line['product']->price->amount,
                        // Catalog prices are GST-inclusive. Declare it so that,
                        // when Stripe Tax is on, GST is backed OUT of the amount
                        // (session.amount_total == order total) rather than added
                        // 10% on top — matching the inclusive invoice model.
                        'tax_behavior' => 'inclusive',
                    ],
                    'quantity' => $line['quantity'],
                ])->all(),
                'metadata' => ['order_id' => $order->id],
                'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('cart.index'),
            ];

            // GST via Stripe Tax — off unless the account has Stripe Tax set up.
            if (config('company.stripe_automatic_tax')) {
                $sessionData['automatic_tax'] = ['enabled' => true];
                $sessionData['billing_address_collection'] = 'required';
                $sessionData['customer_update'] = ['address' => 'auto'];
            }

            $session = Cashier::stripe()->checkout->sessions->create($sessionData);

            $order->forceFill(['stripe_checkout_session_id' => $session->id])->save();
        } catch (Throwable $e) {
            report($e);
            $order->items()->delete();
            $order->delete();

            return back()->with('error', 'We could not start the payment session. Please try again or contact us.');
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        // Without a session id there is nothing to confirm; guard against a
        // blank binding matching an unrelated null-session order.
        if (blank($sessionId)) {
            return redirect()->route('home');
        }

        $order = Order::where('user_id', $request->user()->id)
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($order === null) {
            return redirect()->route('home');
        }

        // The webhook is the source of truth for payment. If it hasn't landed
        // yet, confirm the session with Stripe before clearing the cart so a
        // cancelled/unpaid return doesn't wipe it.
        if (! $order->isPaid()) {
            try {
                $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);
            } catch (Throwable $e) {
                report($e);

                return redirect()->route('cart.index');
            }

            if (($session->payment_status ?? null) !== 'paid') {
                return redirect()->route('cart.index')
                    ->with('error', 'Your payment has not completed yet. Your cart is still here if you want to try again.');
            }
        }

        $this->cart->clear();

        return view('storefront.checkout-success', ['order' => $order]);
    }
}
