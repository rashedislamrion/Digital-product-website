<?php

use App\Domain\Licensing\Services\LicenseService;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Models\Category;
use App\Models\Customer;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVersion;

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'Software Libraries',
        'slug' => 'software-libraries',
        'is_visible' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'title' => 'Horizon Analytics SDK',
        'slug' => 'horizon-analytics-sdk',
        'summary' => 'Client metrics library',
        'description_html' => '<p>Client metrics library</p>',
        'visibility' => ProductVisibility::Published,
        'product_type' => ProductType::Software,
    ]);

    $this->version = ProductVersion::create([
        'product_id' => $this->product->id,
        'version_number' => '2.4.0',
        'status' => ProductVersionStatus::Published,
        'released_at' => now(),
    ]);

    $this->price = Price::create([
        'product_id' => $this->product->id,
        'license_tier_name' => 'Team',
        'max_activation_seats' => 2,
        'amount_minor' => 9900,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'email' => 'techlead@corp.test',
        'name' => 'Tech Lead',
        'country_code' => 'US',
    ]);

    $this->order = Order::create([
        'order_number' => 'ORD-202609-9901',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
        'currency' => 'USD',
        'subtotal_minor' => 9900,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 9900,
        'payment_gateway' => 'sslcommerz',
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'product_version_id' => $this->version->id,
        'price_id' => $this->price->id,
        'historical_product_title' => $this->product->title,
        'historical_tier_name' => $this->price->license_tier_name,
        'unit_amount_minor' => 9900,
    ]);

    // Generate license via LicenseService to get the raw key
    $licenseService = app(LicenseService::class);
    $result = $licenseService->createLicenseForOrderItem($this->orderItem, $this->customer, 2);
    $this->license = $result['license'];
    $this->rawKey = $result['raw_key'];
});

it('activates a new instance and transitions license status from issued to active', function () {
    expect($this->license->status)->toBe(LicenseStatus::Issued)
        ->and($this->license->current_activations_count)->toBe(0);

    $response = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'macbook-pro-hardware-uuid-1'),
        'hostname' => 'dev.local',
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'activations_remaining' => 1,
            'expires_at' => null,
        ])
        ->assertJsonStructure(['instance_id']);

    $this->license->refresh();
    expect($this->license->status)->toBe(LicenseStatus::Active)
        ->and($this->license->current_activations_count)->toBe(1);

    $activation = LicenseActivation::where('license_id', $this->license->id)->first();
    expect($activation)->not->toBeNull()
        ->and($activation->hostname)->toBe('dev.local')
        ->and($activation->is_active)->toBeTrue();
});

it('returns existing activation details when same instance reactivates', function () {
    $fingerprint = hash('sha256', 'macbook-pro-hardware-uuid-1');

    $firstResponse = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
        'hostname' => 'dev.local',
    ]);
    $firstResponse->assertOk();
    $instanceId = $firstResponse->json('instance_id');

    // Second activation with same fingerprint
    $secondResponse = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
        'hostname' => 'dev.local',
    ]);

    $secondResponse->assertOk()
        ->assertJson([
            'valid' => true,
            'instance_id' => $instanceId,
            'activations_remaining' => 1,
        ]);

    $this->license->refresh();
    expect($this->license->current_activations_count)->toBe(1);
});

it('rejects activation when maximum seat limit is exceeded', function () {
    // 1st activation (seat 1/2)
    $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'fingerprint-1'),
        'hostname' => 'server1.local',
    ])->assertOk();

    // 2nd activation (seat 2/2)
    $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'fingerprint-2'),
        'hostname' => 'server2.local',
    ])->assertOk();

    // 3rd activation (exceeds limit)
    $response = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'fingerprint-3'),
        'hostname' => 'server3.local',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'valid' => false,
            'error' => 'Maximum activation seats reached for this license.',
        ]);
});

it('rejects activation with invalid license key', function () {
    $response = $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => 'PROD-INVALID-KEY-1234-9999',
        'instance_fingerprint' => hash('sha256', 'fingerprint-1'),
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'valid' => false,
            'error' => 'License key not found or invalid.',
        ]);
});

it('validates an active instance activation', function () {
    $fingerprint = hash('sha256', 'prod-cluster-node-1');

    // Activate first
    $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
        'hostname' => 'prod-cluster-node-1',
    ])->assertOk();

    // Validate
    $response = $this->postJson(route('api.v1.licenses.validate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'status' => 'active',
            'tier' => 'Team',
            'product_version' => '2.4.0',
        ]);
});

it('rejects validation when fingerprint is not active', function () {
    // Activate on node 1
    $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'node-1'),
    ])->assertOk();

    // Validate on un-activated node 2
    $response = $this->postJson(route('api.v1.licenses.validate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => hash('sha256', 'node-2'),
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'valid' => false,
            'error' => 'Instance fingerprint not activated for this license.',
        ]);
});

it('deactivates an instance and frees the seat', function () {
    $fingerprint = hash('sha256', 'temp-staging-node');

    // Activate
    $this->postJson(route('api.v1.licenses.activate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
    ])->assertOk();

    $this->license->refresh();
    expect($this->license->current_activations_count)->toBe(1);

    // Deactivate
    $response = $this->postJson(route('api.v1.licenses.deactivate'), [
        'license_key' => $this->rawKey,
        'instance_fingerprint' => $fingerprint,
    ]);

    $response->assertOk()
        ->assertJson([
            'deactivated' => true,
            'activations_remaining' => 2,
        ]);

    $this->license->refresh();
    expect($this->license->current_activations_count)->toBe(0);

    $activation = LicenseActivation::where('license_id', $this->license->id)
        ->where('instance_fingerprint', $fingerprint)
        ->first();
    expect($activation->is_active)->toBeFalse()
        ->and($activation->deactivated_at)->not->toBeNull();
});
