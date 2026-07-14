<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

/**
 * Session-backed cart for one-time services. Hosting subscriptions never
 * enter the cart — they check out directly through Cashier in
 * subscription mode.
 */
class Cart
{
    protected const KEY = 'cart.items';

    protected const MAX_QUANTITY = 99;

    public function add(Product $product, int $quantity = 1): void
    {
        if ($product->isSubscription()) {
            throw new InvalidArgumentException('Subscription products cannot be added to the cart.');
        }

        $items = Session::get(self::KEY, []);
        $items[$product->id] = min(($items[$product->id] ?? 0) + max($quantity, 1), self::MAX_QUANTITY);

        Session::put(self::KEY, $items);
    }

    public function remove(Product $product): void
    {
        $items = Session::get(self::KEY, []);
        unset($items[$product->id]);

        Session::put(self::KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    /**
     * Total number of units across all lines, for the nav badge. Derived
     * from lines() so the badge never counts deactivated/deleted products
     * the cart page itself drops.
     */
    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    /** @return Collection<int, array{product: Product, quantity: int, total: Money}> */
    public function lines(): Collection
    {
        $items = Session::get(self::KEY, []);

        if ($items === []) {
            return collect();
        }

        $products = Product::active()->whereIn('id', array_keys($items))->get()->keyBy('id');

        return collect($items)
            ->map(function (int $quantity, int $productId) use ($products) {
                $product = $products->get($productId);

                if ($product === null) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'total' => $product->price->multiply($quantity),
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): Money
    {
        return $this->lines()->reduce(
            fn (Money $carry, array $line) => $carry->add($line['total']),
            Money::zero(),
        );
    }
}
