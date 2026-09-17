<?php

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Enums\ReviewStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DownloadEvent;
use App\Models\DownloadGrant;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Models\Review;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;

it('generates valid 26-character ULID primary keys for all domain models', function () {
    $category = Category::factory()->create();
    expect($category->id)->toBeString()->toHaveLength(26);

    $product = Product::factory()->for($category)->create();
    expect($product->id)->toBeString()->toHaveLength(26);

    $version = ProductVersion::factory()->for($product)->create();
    expect($version->id)->toBeString()->toHaveLength(26);

    $file = ProductFile::factory()->for($version, 'version')->create();
    expect($file->id)->toBeString()->toHaveLength(26);

    $price = Price::factory()->for($product)->create();
    expect($price->id)->toBeString()->toHaveLength(26);

    $customer = Customer::factory()->create();
    expect($customer->id)->toBeString()->toHaveLength(26);

    $order = Order::factory()->for($customer)->create();
    expect($order->id)->toBeString()->toHaveLength(26);

    $orderItem = OrderItem::factory()->for($order)->for($product)->create();
    expect($orderItem->id)->toBeString()->toHaveLength(26);

    $grant = DownloadGrant::factory()->for($orderItem)->for($customer)->create();
    expect($grant->id)->toBeString()->toHaveLength(26);

    $event = DownloadEvent::factory()->for($grant, 'grant')->create();
    expect($event->id)->toBeString()->toHaveLength(26);

    $license = License::factory()->for($orderItem)->for($customer)->for($product)->create();
    expect($license->id)->toBeString()->toHaveLength(26);

    $activation = LicenseActivation::factory()->for($license)->create();
    expect($activation->id)->toBeString()->toHaveLength(26);

    $review = Review::factory()->for($product)->for($customer)->for($orderItem)->create();
    expect($review->id)->toBeString()->toHaveLength(26);

    $webhook = WebhookEvent::factory()->create();
    expect($webhook->id)->toBeString()->toHaveLength(26);

    $audit = AuditLog::factory()->create();
    expect($audit->id)->toBeString()->toHaveLength(26);
});

it('verifies relationship tree and backed enum casts', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create([
        'visibility' => ProductVisibility::Published,
        'product_type' => ProductType::Software,
    ]);
    $price = Price::factory()->for($product)->create([
        'amount_minor' => 4900,
        'currency' => 'USD',
    ]);
    $version = ProductVersion::factory()->for($product)->create([
        'status' => ProductVersionStatus::Published,
    ]);
    $file = ProductFile::factory()->for($version, 'version')->create();

    $customer = Customer::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Paid,
        'subtotal_minor' => 4900,
        'tax_minor' => 245,
        'total_minor' => 5145,
        'currency' => 'USD',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_version_id' => $version->id,
        'price_id' => $price->id,
        'unit_amount_minor' => 4900,
    ]);

    $grant = DownloadGrant::factory()->create([
        'order_item_id' => $orderItem->id,
        'customer_id' => $customer->id,
        'max_download_attempts' => 5,
        'download_count' => 1,
    ]);

    $license = License::factory()->create([
        'order_item_id' => $orderItem->id,
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'status' => LicenseStatus::Active,
        'max_activations' => 5,
        'current_activations_count' => 1,
    ]);

    $review = Review::factory()->create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'order_item_id' => $orderItem->id,
        'status' => ReviewStatus::Published,
    ]);

    // Assert relations
    expect($product->category->id)->toBe($category->id)
        ->and($product->prices)->toHaveCount(1)
        ->and($product->versions)->toHaveCount(1)
        ->and($version->files)->toHaveCount(1)
        ->and($order->customer->id)->toBe($customer->id)
        ->and($order->items)->toHaveCount(1)
        ->and($orderItem->license->id)->toBe($license->id)
        ->and($orderItem->review->id)->toBe($review->id)
        ->and($orderItem->downloadGrants)->toHaveCount(1);

    // Assert currency accessors
    expect($price->amount_formatted)->toBe('49.00 USD')
        ->and($order->total_formatted)->toBe('51.45 USD')
        ->and($order->subtotal_formatted)->toBe('49.00 USD');

    // Assert Enum types
    expect($product->visibility)->toBe(ProductVisibility::Published)
        ->and($product->product_type)->toBe(ProductType::Software)
        ->and($version->status)->toBe(ProductVersionStatus::Published)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($license->status)->toBe(LicenseStatus::Active)
        ->and($review->status)->toBe(ReviewStatus::Published);
});

it('enforces unique constraints on reviews and webhook events', function () {
    $orderItem = OrderItem::factory()->create();
    $customer = Customer::factory()->create();
    $product = $orderItem->product;

    Review::factory()->create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'order_item_id' => $orderItem->id,
    ]);

    // Duplicate review on same order_item_id should fail
    expect(fn () => Review::factory()->create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'order_item_id' => $orderItem->id,
    ]))->toThrow(QueryException::class);

    // Webhook event duplicate on (gateway, event_id)
    WebhookEvent::factory()->create([
        'gateway' => 'paddle',
        'event_id' => 'evt_unique_123',
    ]);

    expect(fn () => WebhookEvent::factory()->create([
        'gateway' => 'paddle',
        'event_id' => 'evt_unique_123',
    ]))->toThrow(QueryException::class);
});
