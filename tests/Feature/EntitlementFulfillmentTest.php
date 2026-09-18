<?php

use App\Domain\Licensing\Services\LicenseService;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Jobs\FulfillOrderJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DownloadEvent;
use App\Models\DownloadGrant;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'Developer Tools',
        'slug' => 'developer-tools',
        'is_visible' => true,
    ]);

    // 1. Software Product with Version & File
    $this->softwareProduct = Product::create([
        'category_id' => $this->category->id,
        'title' => 'DevOps CLI Toolkit',
        'slug' => 'devops-cli-toolkit',
        'summary' => 'Automated deployment suite',
        'description_html' => '<p>Automated deployment suite documentation</p>',
        'visibility' => ProductVisibility::Published,
        'product_type' => ProductType::Software,
    ]);

    $this->softwareVersion = ProductVersion::create([
        'product_id' => $this->softwareProduct->id,
        'version_number' => '2.1.0',
        'status' => ProductVersionStatus::Published,
        'released_at' => now(),
    ]);

    $this->softwareFile = ProductFile::create([
        'product_version_id' => $this->softwareVersion->id,
        'storage_disk' => 's3_secure',
        'storage_path' => 'releases/devops-cli-toolkit-v2.1.0.zip',
        'file_name' => 'devops-cli-toolkit-v2.1.0.zip',
        'file_size_bytes' => 10485760, // 10 MB
        'mime_type' => 'application/zip',
        'checksum_sha256' => hash('sha256', 'mock binary content'),
        'is_scanned_safe' => true,
    ]);

    $this->softwarePrice = Price::create([
        'product_id' => $this->softwareProduct->id,
        'license_tier_name' => 'Team License',
        'max_activation_seats' => 5,
        'amount_minor' => 14900,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    // 2. Ebook Product (Non-software)
    $this->ebookProduct = Product::create([
        'category_id' => $this->category->id,
        'title' => 'Laravel Architecture Handbook',
        'slug' => 'laravel-architecture-handbook',
        'summary' => 'System design handbook',
        'description_html' => '<p>System design handbook</p>',
        'visibility' => ProductVisibility::Published,
        'product_type' => ProductType::Ebook,
    ]);

    $this->ebookVersion = ProductVersion::create([
        'product_id' => $this->ebookProduct->id,
        'version_number' => '1.0.0',
        'status' => ProductVersionStatus::Published,
        'released_at' => now(),
    ]);

    $this->ebookPrice = Price::create([
        'product_id' => $this->ebookProduct->id,
        'license_tier_name' => 'Standard Edition',
        'max_activation_seats' => 0,
        'amount_minor' => 2900,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'email' => 'developer@acme.test',
        'name' => 'Alex Developer',
        'country_code' => 'BD',
    ]);

    $this->order = Order::create([
        'order_number' => 'ORD-202609-8801',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
        'currency' => 'USD',
        'subtotal_minor' => 17800,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 17800,
        'payment_gateway' => 'sslcommerz',
    ]);

    $this->softwareOrderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->softwareProduct->id,
        'product_version_id' => $this->softwareVersion->id,
        'price_id' => $this->softwarePrice->id,
        'historical_product_title' => $this->softwareProduct->title,
        'historical_tier_name' => $this->softwarePrice->license_tier_name,
        'unit_amount_minor' => 14900,
    ]);

    $this->ebookOrderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->ebookProduct->id,
        'product_version_id' => $this->ebookVersion->id,
        'price_id' => $this->ebookPrice->id,
        'historical_product_title' => $this->ebookProduct->title,
        'historical_tier_name' => $this->ebookPrice->license_tier_name,
        'unit_amount_minor' => 2900,
    ]);
});

it('fulfills paid order by generating download grants, cryptographic software licenses, and queuing confirmation mail', function () {
    Mail::fake();

    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    // 1. Verify download grants created for both items
    $grants = DownloadGrant::where('customer_id', $this->customer->id)->get();
    expect($grants)->toHaveCount(2);

    $softwareGrant = $grants->where('order_item_id', $this->softwareOrderItem->id)->first();
    expect($softwareGrant)->not->toBeNull()
        ->and($softwareGrant->max_download_attempts)->toBe(5)
        ->and($softwareGrant->download_count)->toBe(0)
        ->and($softwareGrant->is_revoked)->toBeFalse()
        ->and($softwareGrant->expires_at)->not->toBeNull();

    // 2. Verify software license created with cryptographic key hash and masked representation
    $license = License::where('order_item_id', $this->softwareOrderItem->id)->first();
    expect($license)->not->toBeNull()
        ->and($license->product_id)->toBe($this->softwareProduct->id)
        ->and($license->customer_id)->toBe($this->customer->id)
        ->and($license->status)->toBe(LicenseStatus::Issued)
        ->and($license->max_activations)->toBe(5)
        ->and($license->current_activations_count)->toBe(0)
        ->and(strlen($license->license_key_hash))->toBe(64) // SHA-256
        ->and($license->license_key_masked)->toMatch('/^PROD-XXXX-XXXX-XXXX-[A-Z0-9]{4}$/');

    // 3. Verify non-software ebook product did NOT generate a license
    $ebookLicense = License::where('order_item_id', $this->ebookOrderItem->id)->first();
    expect($ebookLicense)->toBeNull();

    // 4. Verify queued order confirmation email
    Mail::assertQueued(OrderConfirmationMail::class, function ($mail) {
        return $mail->order->id === $this->order->id
            && count($mail->rawLicenses) === 1
            && isset($mail->rawLicenses[$this->softwareOrderItem->id]['raw_key']);
    });
});

it('delivers secure signed S3 download and atomically increments attempt counter', function () {
    // Fulfill order first
    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    $grant = DownloadGrant::where('order_item_id', $this->softwareOrderItem->id)->first();

    expect($grant->download_count)->toBe(0);

    // Hit secure download route as owner
    $response = $this->withSession(['guest_customer_id' => $this->customer->id])
        ->get(route('library.download', ['download_grant' => $grant->id]));

    // Assert 302 redirect with custom binary delivery headers
    $response->assertStatus(302);
    $response->assertHeader('Content-Disposition', 'attachment; filename="devops-cli-toolkit-v2.1.0.zip"');
    $response->assertHeader('Cache-Control', 'no-store, private');

    // Assert download counter atomically incremented
    $grant->refresh();
    expect($grant->download_count)->toBe(1);

    // Assert download_events row created with client telemetry
    $event = DownloadEvent::where('download_grant_id', $grant->id)->latest('downloaded_at')->first();
    expect($event)->not->toBeNull()
        ->and($event->bytes_transferred)->toBe(10485760);
});

it('rejects download attempt and displays friendly error when download limit is reached', function () {
    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    $grant = DownloadGrant::where('order_item_id', $this->softwareOrderItem->id)->first();
    $grant->update(['download_count' => 5, 'max_download_attempts' => 5]);

    $response = $this->withSession(['guest_customer_id' => $this->customer->id])
        ->get(route('library.download', ['download_grant' => $grant->id]));

    $response->assertOk()
        ->assertViewIs('storefront.downloads.invalid')
        ->assertSee('This download link is no longer valid')
        ->assertSee('maximum allowed download attempts');

    // Download count must not increment further
    $grant->refresh();
    expect($grant->download_count)->toBe(5);
});

it('rejects download attempt when grant has expired', function () {
    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    $grant = DownloadGrant::where('order_item_id', $this->softwareOrderItem->id)->first();
    $grant->update(['expires_at' => now()->subDay()]);

    $response = $this->withSession(['guest_customer_id' => $this->customer->id])
        ->get(route('library.download', ['download_grant' => $grant->id]));

    $response->assertOk()
        ->assertViewIs('storefront.downloads.invalid')
        ->assertSee('This download link is no longer valid')
        ->assertSee('has expired');
});

it('rejects download attempt when grant has been revoked', function () {
    $job = new FulfillOrderJob($this->order->id);
    $job->handle(app(LicenseService::class));

    $grant = DownloadGrant::where('order_item_id', $this->softwareOrderItem->id)->first();
    $grant->update(['is_revoked' => true]);

    $response = $this->withSession(['guest_customer_id' => $this->customer->id])
        ->get(route('library.download', ['download_grant' => $grant->id]));

    $response->assertOk()
        ->assertViewIs('storefront.downloads.invalid')
        ->assertSee('This download link is no longer valid')
        ->assertSee('revoked due to an order refund');
});
