<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Throwable;

class SubscriptionCheckoutController extends Controller
{
    /**
     * Send the customer to Stripe Checkout in subscription mode for a
     * hosting plan. Cashier's webhook handling keeps the local
     * subscriptions tables in sync from there.
     */
    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active && $product->isSubscription(), 404);

        if (blank(config('cashier.secret')) || blank($product->stripe_price_id)) {
            return back()->with('error', 'Online subscription signup is not available yet — please contact us to activate this plan.');
        }

        // One active hosting plan per client; prevent double-billing from a
        // second 'default' subscription.
        if ($request->user()->subscribed('default')) {
            return back()->with('error', 'You already have an active hosting plan — please contact us to switch or upgrade.');
        }

        try {
            $options = [
                'success_url' => route('services.show', $product).'?subscribed=1',
                'cancel_url' => route('services.show', $product),
            ];

            // GST via Stripe Tax — off unless the account has Stripe Tax set up.
            if (config('company.stripe_automatic_tax')) {
                $options['automatic_tax'] = ['enabled' => true];
                $options['customer_update'] = ['address' => 'auto'];
            }

            return $request->user()
                ->newSubscription('default', $product->stripe_price_id)
                ->checkout($options);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'We could not start the subscription signup. Please try again or contact us.');
        }
    }
}
