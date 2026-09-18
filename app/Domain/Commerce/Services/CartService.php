<?php

namespace App\Domain\Commerce\Services;

use App\Models\Coupon;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    protected const CART_SESSION_KEY = 'storefront_cart';

    protected const COUPON_SESSION_KEY = 'storefront_applied_coupon';

    /**
     * Get raw items from session.
     */
    public function getRawCart(): array
    {
        return session()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Add a product and price tier to the cart.
     */
    public function add(string $productId, string $priceId, int $quantity = 1): void
    {
        $cart = $this->getRawCart();

        // If price already exists, keep or update quantity
        if (isset($cart[$priceId])) {
            $cart[$priceId]['quantity'] = max(1, $cart[$priceId]['quantity'] + $quantity);
        } else {
            $cart[$priceId] = [
                'product_id' => $productId,
                'price_id' => $priceId,
                'quantity' => max(1, $quantity),
            ];
        }

        session()->put(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Set a single item for express "Buy Now" checkout.
     */
    public function setSingleItem(string $productId, string $priceId): void
    {
        $cart = [
            $priceId => [
                'product_id' => $productId,
                'price_id' => $priceId,
                'quantity' => 1,
            ],
        ];

        session()->put(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Remove an item from the cart by its price ID.
     */
    public function remove(string $priceId): void
    {
        $cart = $this->getRawCart();
        unset($cart[$priceId]);

        session()->put(self::CART_SESSION_KEY, $cart);

        // Re-validate coupon against updated subtotal
        $this->revalidateCoupon();
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(): void
    {
        session()->forget(self::CART_SESSION_KEY);
        session()->forget(self::COUPON_SESSION_KEY);
    }

    /**
     * Get hydrated collection of cart items.
     *
     * @return Collection<int, object>
     */
    public function getItems(): Collection
    {
        $raw = $this->getRawCart();

        if (empty($raw)) {
            return collect();
        }

        $priceIds = array_keys($raw);
        $prices = Price::with('product')->whereIn('id', $priceIds)->get()->keyBy('id');

        $items = collect();

        foreach ($raw as $priceId => $data) {
            $price = $prices->get($priceId);

            if (! $price || ! $price->product || ! $price->product->isPublished()) {
                // If price or product is no longer active, skip / prune
                continue;
            }

            $product = $price->product;
            $quantity = $data['quantity'] ?? 1;
            $lineTotalMinor = $price->amount_minor * $quantity;

            $items->push((object) [
                'price_id' => $priceId,
                'product_id' => $product->id,
                'product' => $product,
                'price' => $price,
                'tier_name' => $price->license_tier_name,
                'unit_amount_minor' => $price->amount_minor,
                'unit_amount_formatted' => $price->amount_formatted,
                'quantity' => $quantity,
                'line_total_minor' => $lineTotalMinor,
                'line_total_formatted' => number_format($lineTotalMinor / 100, 2).' '.$price->currency,
            ]);
        }

        return $items;
    }

    /**
     * Total number of line items in cart.
     */
    public function getCount(): int
    {
        return count($this->getRawCart());
    }

    /**
     * Calculate subtotal in minor units.
     */
    public function getSubtotalMinor(): int
    {
        return (int) $this->getItems()->sum('line_total_minor');
    }

    /**
     * Formatted subtotal string.
     */
    public function getSubtotalFormatted(): string
    {
        return '$'.number_format($this->getSubtotalMinor() / 100, 2);
    }

    /**
     * Apply a coupon code.
     */
    public function applyCoupon(string $code): array
    {
        $code = strtoupper(trim($code));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return [
                'success' => false,
                'message' => 'Invalid promotional or coupon code.',
            ];
        }

        $subtotal = $this->getSubtotalMinor();

        if (! $coupon->isValidForAmount($subtotal)) {
            if ($coupon->min_order_amount_minor && $subtotal < $coupon->min_order_amount_minor) {
                $minDollars = number_format($coupon->min_order_amount_minor / 100, 2);

                return [
                    'success' => false,
                    'message' => "This coupon requires a minimum cart subtotal of \${$minDollars}.",
                ];
            }

            return [
                'success' => false,
                'message' => 'This coupon has expired or reached its maximum redemptions.',
            ];
        }

        session()->put(self::COUPON_SESSION_KEY, $coupon->code);

        return [
            'success' => true,
            'message' => "Coupon '{$coupon->code}' applied successfully.",
        ];
    }

    /**
     * Remove the currently applied coupon.
     */
    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_SESSION_KEY);
    }

    /**
     * Get the active applied coupon model.
     */
    public function getAppliedCoupon(): ?Coupon
    {
        $code = session()->get(self::COUPON_SESSION_KEY);

        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValidForAmount($this->getSubtotalMinor())) {
            session()->forget(self::COUPON_SESSION_KEY);

            return null;
        }

        return $coupon;
    }

    /**
     * Calculate discount amount in minor units.
     */
    public function getDiscountMinor(): int
    {
        $coupon = $this->getAppliedCoupon();

        if (! $coupon) {
            return 0;
        }

        return $coupon->calculateDiscount($this->getSubtotalMinor());
    }

    /**
     * Formatted discount string.
     */
    public function getDiscountFormatted(): string
    {
        return '$'.number_format($this->getDiscountMinor() / 100, 2);
    }

    /**
     * Calculate final order total in minor units.
     */
    public function getTotalMinor(): int
    {
        return max(0, $this->getSubtotalMinor() - $this->getDiscountMinor());
    }

    /**
     * Formatted final total string.
     */
    public function getTotalFormatted(): string
    {
        return '$'.number_format($this->getTotalMinor() / 100, 2);
    }

    /**
     * Revalidate coupon when cart changes.
     */
    protected function revalidateCoupon(): void
    {
        $coupon = $this->getAppliedCoupon();
        if ($coupon && ! $coupon->isValidForAmount($this->getSubtotalMinor())) {
            $this->removeCoupon();
        }
    }
}
