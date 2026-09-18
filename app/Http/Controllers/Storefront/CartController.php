<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Commerce\Services\CartService;
use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    /**
     * Add a product/price to the cart.
     */
    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'price_id' => ['required', 'string', 'exists:prices,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::published()->findOrFail($validated['product_id']);
        $price = Price::where('product_id', $product->id)
            ->where('is_active', true)
            ->findOrFail($validated['price_id']);

        $quantity = (int) ($validated['quantity'] ?? 1);

        if ($request->boolean('buy_now')) {
            $this->cartService->setSingleItem($product->id, $price->id);

            return redirect()->route('checkout');
        }

        $this->cartService->add($product->id, $price->id, $quantity);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Added {$product->title} ({$price->license_tier_name}) to cart.",
                'cart' => $this->getCartPayload(),
            ]);
        }

        return redirect()->back()
            ->with('status', "Added {$product->title} to your cart.")
            ->with('open_cart', true);
    }

    /**
     * Remove a line item from the cart.
     */
    public function remove(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'price_id' => ['required', 'string'],
        ]);

        $this->cartService->remove($validated['price_id']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart' => $this->getCartPayload(),
            ]);
        }

        return redirect()->back()
            ->with('status', 'Item removed from your cart.')
            ->with('open_cart', true);
    }

    /**
     * Apply a coupon code.
     */
    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->cartService->applyCoupon($validated['code']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'cart' => $this->getCartPayload(),
            ], $result['success'] ? 200 : 422);
        }

        if (! $result['success']) {
            return redirect()->back()
                ->withErrors(['coupon' => $result['message']])
                ->with('open_cart', true);
        }

        return redirect()->back()
            ->with('status', $result['message'])
            ->with('open_cart', true);
    }

    /**
     * Remove the active coupon.
     */
    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $this->cartService->removeCoupon();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon removed.',
                'cart' => $this->getCartPayload(),
            ]);
        }

        return redirect()->back()
            ->with('status', 'Coupon removed.')
            ->with('open_cart', true);
    }

    /**
     * Return cart state for reactive Alpine.js drawer.
     */
    public function data(): JsonResponse
    {
        return response()->json($this->getCartPayload());
    }

    /**
     * Build standard cart response payload.
     */
    protected function getCartPayload(): array
    {
        $items = $this->cartService->getItems()->map(function ($item) {
            return [
                'price_id' => $item->price_id,
                'product_id' => $item->product_id,
                'product_title' => $item->product->title,
                'product_slug' => $item->product->slug,
                'tier_name' => $item->tier_name,
                'unit_amount_formatted' => $item->unit_amount_formatted,
                'quantity' => $item->quantity,
                'line_total_formatted' => $item->line_total_formatted,
                'thumbnail_url' => $item->product->thumbnail_url,
            ];
        });

        $coupon = $this->cartService->getAppliedCoupon();

        return [
            'items' => $items,
            'count' => $this->cartService->getCount(),
            'subtotal_minor' => $this->cartService->getSubtotalMinor(),
            'subtotal_formatted' => $this->cartService->getSubtotalFormatted(),
            'discount_minor' => $this->cartService->getDiscountMinor(),
            'discount_formatted' => $this->cartService->getDiscountFormatted(),
            'total_minor' => $this->cartService->getTotalMinor(),
            'total_formatted' => $this->cartService->getTotalFormatted(),
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ] : null,
        ];
    }
}
