<?php

use App\Domain\Commerce\Services\CartService;
use App\Models\Coupon;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('adds a product and license tier to the session cart', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $response = $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response->assertSessionHas('open_cart', true);

    $cartService = app(CartService::class);
    expect($cartService->getCount())->toBe(1);
    expect($cartService->getItems()->first()->price_id)->toBe($price->id);
    expect($cartService->getSubtotalMinor())->toBe($price->amount_minor);
});

it('removes an item from the cart', function () {
    $cartService = app(CartService::class);
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $cartService->add($product->id, $price->id, 1);
    expect($cartService->getCount())->toBe(1);

    $response = $this->post('/cart/remove', [
        'price_id' => $price->id,
    ]);

    $response->assertSessionHas('open_cart', true);
    expect($cartService->getCount())->toBe(0);
    expect($cartService->getSubtotalMinor())->toBe(0);
});

it('sets single item and redirects to checkout on Buy Now', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $response = $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
        'buy_now' => 1,
    ]);

    $response->assertRedirect('/checkout');

    $cartService = app(CartService::class);
    expect($cartService->getCount())->toBe(1);
    expect($cartService->getItems()->first()->price_id)->toBe($price->id);
});

it('applies a valid coupon code and calculates discount', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    $coupon = Coupon::create([
        'code' => 'TEST50',
        'discount_type' => 'percent',
        'discount_value' => 50,
        'min_order_amount_minor' => 1000,
        'is_active' => true,
    ]);

    $response = $this->post('/cart/coupon', [
        'code' => 'TEST50',
    ]);

    $response->assertSessionHasNoErrors();
    $cartService = app(CartService::class);
    expect($cartService->getAppliedCoupon()?->code)->toBe('TEST50');

    $subtotal = $cartService->getSubtotalMinor();
    $discount = $cartService->getDiscountMinor();
    expect($discount)->toBe((int) round($subtotal * 0.5));
    expect($cartService->getTotalMinor())->toBe($subtotal - $discount);
});

it('rejects an invalid or inactive coupon code', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    $response = $this->post('/cart/coupon', [
        'code' => 'INVALIDCODE999',
    ]);

    $response->assertSessionHasErrors(['coupon']);
    $cartService = app(CartService::class);
    expect($cartService->getAppliedCoupon())->toBeNull();
});

it('removes an applied coupon', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    Coupon::create([
        'code' => 'REMOVE20',
        'discount_type' => 'percent',
        'discount_value' => 20,
        'is_active' => true,
    ]);

    $this->post('/cart/coupon', ['code' => 'REMOVE20']);

    $cartService = app(CartService::class);
    expect($cartService->getAppliedCoupon()?->code)->toBe('REMOVE20');

    $this->delete('/cart/coupon')->assertSessionHasNoErrors();
    expect($cartService->getAppliedCoupon())->toBeNull();
    expect($cartService->getDiscountMinor())->toBe(0);
});
