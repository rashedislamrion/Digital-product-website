<?php

use App\Domain\Commerce\Services\RevokeOrderAccess;
use App\Domain\Licensing\Services\LicenseService;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Jobs\FulfillOrderJob;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DownloadGrant;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Models\User;

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'Backend Frameworks',
        'slug' => 'backend-frameworks',
        'is_visible' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'title' => 'Nova Enterprise Suite',
        'slug' => 'nova-enterprise-suite',
        'summary' => 'Enterprise admin architecture',
        'description_html' => '<p>Enterprise admin architecture for modern PHP applications.</p>',
        'visibility' => ProductVisibility::Published,
        'product_type' => ProductType::Software,
    ]);

    $this->version = ProductVersion::create([
        'product_id' => $this->product->id,
        'version_number' => '3.0.0',
        'status' => ProductVersionStatus::Published,
        'released_at' => now(),
    ]);

    $this->file = ProductFile::create([
        'product_version_id' => $this->version->id,
        'storage_disk' => 's3_secure',
        'storage_path' => 'releases/nova-v3.0.0.zip',
        'file_name' => 'nova-v3.0.0.zip',
        'file_size_bytes' => 5242880,
        'mime_type' => 'application/zip',
        'checksum_sha256' => hash('sha256', 'mock content'),
        'is_scanned_safe' => true,
    ]);

    $this->price = Price::create([
        'product_id' => $this->product->id,
        'license_tier_name' => 'Standard',
        'max_activation_seats' => 1,
        'amount_minor' => 7900,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'email' => 'client@enterprise.test',
        'name' => 'Client Corp',
        'country_code' => 'US',
    ]);

    $this->order = Order::create([
        'order_number' => 'ORD-202609-7701',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
        'currency' => 'USD',
        'subtotal_minor' => 7900,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 7900,
        'payment_gateway' => 'sslcommerz',
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'product_version_id' => $this->version->id,
        'price_id' => $this->price->id,
        'historical_product_title' => $this->product->title,
        'historical_tier_name' => $this->price->license_tier_name,
        'unit_amount_minor' => 7900,
    ]);

    // Fulfill order
    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    $this->grant = DownloadGrant::where('order_item_id', $this->orderItem->id)->first();
    $this->license = License::where('order_item_id', $this->orderItem->id)->first();

    $this->admin = User::factory()->create([
        'email' => 'finance-admin@storefront.test',
    ]);
});

it('revokes order access, invalidating download grants and software licenses, and records audit log', function () {
    expect($this->order->status)->toBe(OrderStatus::Paid)
        ->and($this->grant->is_revoked)->toBeFalse()
        ->and($this->license->status)->toBe(LicenseStatus::Issued);

    $revokeService = app(RevokeOrderAccess::class);
    $refundedOrder = $revokeService->execute(
        $this->order,
        $this->admin,
        'Customer requested chargeback'
    );

    // 1. Order status is refunded
    expect($refundedOrder->status)->toBe(OrderStatus::Refunded);

    // 2. Download grant is revoked
    $this->grant->refresh();
    expect($this->grant->is_revoked)->toBeTrue();

    // 3. License is revoked
    $this->license->refresh();
    expect($this->license->status)->toBe(LicenseStatus::Revoked);

    // 4. Audit log entry recorded
    $auditLog = AuditLog::where('target_id', $this->order->id)
        ->where('action', 'order.refund')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->admin->id)
        ->and($auditLog->metadata_before['status'])->toBe('paid')
        ->and($auditLog->metadata_after['status'])->toBe('refunded')
        ->and($auditLog->metadata_after['revoked_grants_count'])->toBe(1)
        ->and($auditLog->metadata_after['revoked_licenses_count'])->toBe(1);
});

it('denies downloads for revoked orders', function () {
    $revokeService = app(RevokeOrderAccess::class);
    $revokeService->execute($this->order, $this->admin, 'Refund');

    $response = $this->withSession(['guest_customer_id' => $this->customer->id])
        ->get(route('library.download', ['download_grant' => $this->grant->id]));

    $response->assertOk()
        ->assertViewIs('storefront.downloads.invalid')
        ->assertSee('revoked due to an order refund');
});

it('denies license activations for revoked orders', function () {
    $revokeService = app(RevokeOrderAccess::class);
    $revokeService->execute($this->order, $this->admin, 'Refund');

    // Attempt to activate via API with dummy key that hashes to the license
    $this->license->refresh();
    $rawKey = 'PROD-TEST-REVK-1234-5678';
    $this->license->update(['license_key_hash' => hash('sha256', $rawKey)]);

    $response = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $rawKey,
        'instance_fingerprint' => hash('sha256', 'machine-1'),
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'valid' => false,
            'error' => 'License is revoked.',
        ]);
});

it('revokes order via artisan command order:revoke', function () {
    $this->artisan('order:revoke', [
        'order' => $this->order->order_number,
        '--reason' => 'CLI automated refund test',
    ])->assertSuccessful();

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Refunded);

    $this->grant->refresh();
    expect($this->grant->is_revoked)->toBeTrue();

    $this->license->refresh();
    expect($this->license->status)->toBe(LicenseStatus::Revoked);
});
