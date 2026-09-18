<?php

use App\Domain\Commerce\Services\CartService;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('redirects empty cart away from checkout', function () {
    $this->get('/checkout')->assertRedirect(route('products.index'));
});

it('renders checkout view with line items when cart is not empty', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    $response = $this->get('/checkout');

    $response->assertOk()
        ->assertSee('Single-Step Express Checkout')
        ->assertSee($product->title)
        ->assertSee($price->license_tier_name)
        ->assertSee('Authorize Payment with SSLCOMMERZ');
});

it('validates email and country on checkout submission', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    $response = $this->post('/checkout', [
        'email' => 'not-an-email',
        'country' => '',
        'payment_method' => 'sslcommerz',
    ]);

    $response->assertSessionHasErrors(['email', 'country']);
});

it('creates a pending order with historical item snapshots and redirects to gateway', function () {
    $product = Product::published()->first();
    $price = $product->prices()->first();

    $this->post('/cart/add', [
        'product_id' => $product->id,
        'price_id' => $price->id,
    ]);

    // Mock SSLCOMMERZ Hosted Session Initialization API
    Http::fake([
        '*/gwprocess/v4/api.php' => Http::response([
            'status' => 'SUCCESS',
            'failedreason' => '',
            'sessionkey' => 'TEST_SESSION_KEY_12345',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/gw.php?Q=pay&SESSIONKEY=TEST_SESSION_KEY_12345',
        ], 200),
    ]);

    $response = $this->post('/checkout', [
        'email' => 'buyer@example.com',
        'country' => 'Bangladesh',
        'payment_method' => 'sslcommerz',
    ]);

    // Must redirect to the GatewayPageURL
    $response->assertRedirect('https://sandbox.sslcommerz.com/gwprocess/v4/gw.php?Q=pay&SESSIONKEY=TEST_SESSION_KEY_12345');

    // Verify order was created in DB
    $order = Order::whereHas('customer', fn ($q) => $q->where('email', 'buyer@example.com'))->latest()->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->payment_gateway)->toBe('sslcommerz');
    expect($order->total_minor)->toBe($price->amount_minor);

    // Verify immutable snapshot on order_items
    $item = $order->items->first();
    expect($item->historical_product_title)->toBe($product->title);
    expect($item->historical_tier_name)->toBe($price->license_tier_name);
    expect($item->unit_amount_minor)->toBe($price->amount_minor);

    // Verify cart was cleared
    $cartService = app(CartService::class);
    expect($cartService->getCount())->toBe(0);
});
