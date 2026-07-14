<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected Cart $cart) {}

    public function index(): View
    {
        return view('storefront.cart', [
            'lines' => $this->cart->lines(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if ($product->isSubscription()) {
            return redirect()
                ->route('services.show', $product)
                ->with('error', 'Hosting plans are subscriptions — subscribe directly from the plan page.');
        }

        $this->cart->add($product);

        return redirect()
            ->route('cart.index')
            ->with('success', "{$product->name} added to your cart.");
    }

    public function remove(Product $product): RedirectResponse
    {
        $this->cart->remove($product);

        return redirect()->route('cart.index');
    }
}
