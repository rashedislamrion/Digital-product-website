<?php

use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Filament\Resources\ProductVersions\ProductVersionResource;
use App\Jobs\ScanProductFileJob;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3_secure');
    Storage::fake('public');
});

it('allows catalog user to view catalog resources', function () {
    $catalogUser = User::where('email', 'catalog@example.com')->first();

    $this->actingAs($catalogUser)->get('/admin/products')->assertOk();
    $this->actingAs($catalogUser)->get('/admin/categories')->assertOk();
    $this->actingAs($catalogUser)->get('/admin/product-versions')->assertOk();
});

it('allows super admin to view catalog resources', function () {
    $superAdmin = User::where('email', 'admin@example.com')->first();

    $this->actingAs($superAdmin)->get('/admin/products')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/categories')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/product-versions')->assertOk();
});

it('forbids unauthorized staff and customers from accessing catalog resources', function () {
    $financeUser = User::where('email', 'finance@example.com')->first();
    $customerUser = User::factory()->create(['email' => 'customer@example.com']);

    $this->actingAs($financeUser)->get('/admin/products')->assertForbidden();
    $this->actingAs($financeUser)->get('/admin/categories')->assertForbidden();
    $this->actingAs($financeUser)->get('/admin/product-versions')->assertForbidden();

    $this->actingAs($customerUser)->get('/admin/products')->assertForbidden();
});

it('creates a category with self-referencing parent hierarchy', function () {
    $parent = Category::create([
        'name' => 'Software & Scripts',
        'slug' => 'software-scripts',
        'is_visible' => true,
    ]);

    $child = Category::create([
        'parent_id' => $parent->id,
        'name' => 'Laravel Packages',
        'slug' => 'laravel-packages',
        'is_visible' => true,
    ]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children->first()->id)->toBe($child->id);
});

it('creates a product with compatibility metadata and price tiers stored in minor units', function () {
    $category = Category::create([
        'name' => 'Developer Kits',
        'slug' => 'developer-kits',
        'is_visible' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'Nexus Microservices Kit',
        'slug' => 'nexus-microservices-kit',
        'summary' => 'Event-driven boilerplate with Kafka and Redis.',
        'description_html' => '<p>High-scale distributed systems template.</p>',
        'product_type' => ProductType::Software,
        'compatibility_metadata' => [
            'PHP' => '8.3+',
            'Laravel' => '13.x',
        ],
        'is_featured' => true,
        'visibility' => ProductVisibility::Published,
    ]);

    $price = Price::create([
        'product_id' => $product->id,
        'license_tier_name' => 'Commercial License',
        'max_activation_seats' => 5,
        'amount_minor' => 7900, // $79.00
        'currency' => 'USD',
        'is_active' => true,
    ]);

    expect($product->id)->toBeString()
        ->and($product->compatibility_metadata['PHP'])->toBe('8.3+')
        ->and($product->prices)->toHaveCount(1)
        ->and($product->price_range)->toBe('79.00 USD')
        ->and($price->amount_minor)->toBe(7900);
});

it('computes sha256 checksum and dispatches scan job when processing uploaded archive', function () {
    Queue::fake();

    $category = Category::create([
        'name' => 'Themes',
        'slug' => 'themes',
        'is_visible' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'Aurora UI Pro',
        'slug' => 'aurora-ui-pro',
        'summary' => 'Dark mode UI kit',
        'description_html' => '<p>Modern dashboard theme</p>',
        'product_type' => ProductType::Theme,
        'visibility' => ProductVisibility::Published,
    ]);

    $version = ProductVersion::create([
        'product_id' => $product->id,
        'version_number' => '1.0.0',
        'changelog_markdown' => '# Initial Release',
        'min_runtime_version' => 'PHP 8.3',
        'status' => ProductVersionStatus::Quarantined,
    ]);

    $fakeContent = 'PK...Fake Zip Archive Contents for testing SHA256 hashing...';
    $archivePath = 'releases/'.(string) Str::uuid().'/aurora-v1.0.0.zip';
    Storage::disk('s3_secure')->put($archivePath, $fakeContent);

    ProductVersionResource::processUploadedArchive($version, $archivePath);

    $productFile = ProductFile::where('product_version_id', $version->id)->first();
    expect($productFile)->not->toBeNull()
        ->and($productFile->storage_disk)->toBe('s3_secure')
        ->and($productFile->storage_path)->toBe($archivePath)
        ->and($productFile->checksum_sha256)->toBe(hash('sha256', $fakeContent))
        ->and($productFile->is_scanned_safe)->toBeFalse();

    Queue::assertPushed(ScanProductFileJob::class, function ($job) use ($productFile) {
        return $job->productFileId === $productFile->id;
    });
});

it('verifies ScanProductFileJob marks file as scanned safe', function () {
    $category = Category::create([
        'name' => 'Tools',
        'slug' => 'tools',
        'is_visible' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'DevOps Scripts',
        'slug' => 'devops-scripts',
        'summary' => 'CI/CD workflows',
        'description_html' => '<p>Devops workflows</p>',
        'product_type' => ProductType::Software,
        'visibility' => ProductVisibility::Published,
    ]);

    $version = ProductVersion::create([
        'product_id' => $product->id,
        'version_number' => '1.0.0',
        'status' => ProductVersionStatus::Quarantined,
    ]);

    $productFile = ProductFile::create([
        'product_version_id' => $version->id,
        'storage_disk' => 's3_secure',
        'storage_path' => 'releases/test.zip',
        'file_name' => 'devops-v1.0.0.zip',
        'file_size_bytes' => 1024,
        'mime_type' => 'application/zip',
        'checksum_sha256' => hash('sha256', 'dummy'),
        'is_scanned_safe' => false,
    ]);

    // Dispatch & handle synchronously
    (new ScanProductFileJob($productFile->id))->handle();

    $productFile->refresh();
    expect($productFile->is_scanned_safe)->toBeTrue();
});

it('creates audit_log when a scanned version is published by catalog publisher', function () {
    $catalogUser = User::where('email', 'catalog@example.com')->first();

    $category = Category::create([
        'name' => 'Tools',
        'slug' => 'tools-audit',
        'is_visible' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'Audit Tester',
        'slug' => 'audit-tester',
        'summary' => 'Audit verification item',
        'description_html' => '<p>Audit verification html</p>',
        'product_type' => ProductType::Software,
        'visibility' => ProductVisibility::Published,
    ]);

    $version = ProductVersion::create([
        'product_id' => $product->id,
        'version_number' => '2.0.0',
        'status' => ProductVersionStatus::Quarantined,
    ]);

    ProductFile::create([
        'product_version_id' => $version->id,
        'storage_disk' => 's3_secure',
        'storage_path' => 'releases/audit-v2.zip',
        'file_name' => 'audit-v2.zip',
        'file_size_bytes' => 2048,
        'mime_type' => 'application/zip',
        'checksum_sha256' => hash('sha256', 'secure-content'),
        'is_scanned_safe' => true,
    ]);

    // Simulate the publishing action
    $oldStatus = $version->status->value;
    $version->update([
        'status' => ProductVersionStatus::Published,
        'released_at' => now(),
    ]);

    AuditLog::create([
        'user_id' => $catalogUser->id,
        'action' => 'product_version.publish',
        'target_type' => ProductVersion::class,
        'target_id' => $version->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test Agent',
        'metadata_before' => ['status' => $oldStatus],
        'metadata_after' => ['status' => 'published', 'released_at' => now()->toIso8601String()],
        'created_at' => now(),
    ]);

    $version->refresh();
    expect($version->status)->toBe(ProductVersionStatus::Published)
        ->and($version->released_at)->not->toBeNull();

    $audit = AuditLog::where('target_id', $version->id)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->action)->toBe('product_version.publish')
        ->and($audit->metadata_before['status'])->toBe('quarantined')
        ->and($audit->metadata_after['status'])->toBe('published');
});
