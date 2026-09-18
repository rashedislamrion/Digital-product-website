<?php

use App\Enums\OrderStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\FulfillOrderJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WebhookEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->product = Product::published()->first();
    $this->price = $this->product->prices()->first();

    $this->customer = Customer::firstOrCreate(
        ['email' => 'buyer-payment-test@example.com'],
        ['currency' => 'USD']
    );

    $this->order = Order::create([
        'order_number' => 'ORD-TEST-'.rand(1000, 9999),
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Pending,
        'currency' => 'USD',
        'subtotal_minor' => $this->price->amount_minor,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => $this->price->amount_minor,
        'payment_gateway' => 'sslcommerz',
    ]);

    OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'price_id' => $this->price->id,
        'historical_product_title' => $this->product->title,
        'historical_tier_name' => $this->price->license_tier_name,
        'unit_amount_minor' => $this->price->amount_minor,
    ]);
});

it('validates success callback with mandatory server-to-server validation, marks order paid and logs webhook event', function () {
    Queue::fake();

    $valId = 'VAL_ID_SUCCESS_'.rand(10000, 99999);

    // Mock SSLCOMMERZ Order Validation API
    Http::fake([
        '*/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_date' => '2026-09-17 19:30:00',
            'tran_id' => $this->order->order_number,
            'val_id' => $valId,
            'amount' => number_format($this->order->total_minor / 100, 2, '.', ''),
            'store_amount' => number_format($this->order->total_minor / 100, 2, '.', ''),
            'currency' => 'USD',
            'bank_tran_id' => 'BANK_REF_98765',
            'card_type' => 'VISA',
            'card_no' => '411111XXXXXX1111',
            'card_issuer' => 'Standard Chartered Bank',
            'card_brand' => 'VISA',
        ], 200),
    ]);

    $response = $this->post('/payment/sslcommerz/success', [
        'val_id' => $valId,
        'tran_id' => $this->order->order_number,
        'amount' => number_format($this->order->total_minor / 100, 2, '.', ''),
        'currency' => 'USD',
    ]);

    $response->assertRedirect(route('orders.confirmation', ['order_number' => $this->order->order_number]));

    // Assert order status updated to Paid
    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Paid);

    // Assert webhook_events row created with status processed
    $event = WebhookEvent::where('gateway', 'sslcommerz')->where('event_id', $valId)->first();
    expect($event)->not->toBeNull();
    expect($event->status)->toBe(WebhookEventStatus::Processed);

    // Assert fulfillment job dispatched
    Queue::assertPushed(FulfillOrderJob::class, function ($job) {
        return $job->orderId === $this->order->id;
    });
});

it('enforces webhook idempotency on duplicate success callback', function () {
    Queue::fake();

    $valId = 'VAL_ID_IDEMPOTENT_'.rand(10000, 99999);

    // Pre-record processed event
    WebhookEvent::create([
        'gateway' => 'sslcommerz',
        'event_id' => $valId,
        'event_type' => 'order.validated',
        'raw_payload' => ['sample' => 'payload'],
        'status' => WebhookEventStatus::Processed,
        'processed_at' => now(),
    ]);

    $this->order->update(['status' => OrderStatus::Paid]);

    // Send duplicate callback
    $response = $this->post('/payment/sslcommerz/success', [
        'val_id' => $valId,
        'tran_id' => $this->order->order_number,
    ]);

    $response->assertRedirect(route('orders.confirmation', ['order_number' => $this->order->order_number]));

    // Job should NOT be pushed again
    Queue::assertNothingPushed();
});

it('marks order failed when order validation API returns non-valid status', function () {
    Queue::fake();

    $valId = 'VAL_ID_INVALID_'.rand(10000, 99999);

    // Mock SSLCOMMERZ validation returning INVALID status
    Http::fake([
        '*/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'INVALID_TRANSACTION',
            'error' => 'Fraud detection alert or invalid hash.',
        ], 200),
    ]);

    $response = $this->post('/payment/sslcommerz/success', [
        'val_id' => $valId,
        'tran_id' => $this->order->order_number,
    ]);

    $response->assertRedirect(route('checkout.payment-failed', ['order' => $this->order->order_number]));

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Failed);

    $event = WebhookEvent::where('gateway', 'sslcommerz')->where('event_id', $valId)->first();
    expect($event)->not->toBeNull();
    expect($event->status)->toBe(WebhookEventStatus::Failed);

    Queue::assertNothingPushed();
});

it('handles asynchronous server-to-server IPN listener and marks order paid', function () {
    Queue::fake();

    $valId = 'VAL_ID_IPN_'.rand(10000, 99999);

    Http::fake([
        '*/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALIDATED',
            'tran_id' => $this->order->order_number,
            'val_id' => $valId,
            'amount' => number_format($this->order->total_minor / 100, 2, '.', ''),
            'currency' => 'USD',
        ], 200),
    ]);

    $response = $this->postJson('/payment/sslcommerz/ipn', [
        'val_id' => $valId,
        'tran_id' => $this->order->order_number,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'order_number' => $this->order->order_number,
        ]);

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Paid);
});

it('marks order as failed when user cancels on payment gateway', function () {
    $response = $this->post('/payment/sslcommerz/cancel', [
        'tran_id' => $this->order->order_number,
    ]);

    $response->assertRedirect(route('checkout.payment-failed', [
        'order' => $this->order->order_number,
        'reason' => 'Transaction was cancelled by user.',
    ]));

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Failed);
});

it('renders order confirmation screen with order reference and digital entitlement placeholder', function () {
    $this->order->update(['status' => OrderStatus::Paid]);

    $response = $this->get(route('orders.confirmation', ['order_number' => $this->order->order_number]));

    $response->assertOk()
        ->assertSee('Order Confirmed')
        ->assertSee($this->order->order_number)
        ->assertSee($this->customer->email)
        ->assertSee($this->product->title)
        ->assertSee($this->price->license_tier_name)
        ->assertSee('PAID')
        ->assertSee('Your files and license keys will appear here shortly.');
});
