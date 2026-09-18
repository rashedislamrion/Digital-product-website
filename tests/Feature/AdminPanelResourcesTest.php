<?php

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Enums\SupportTicketStatus;
use App\Enums\WebhookEventStatus;
use App\Models\Affiliate;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DownloadEvent;
use App\Models\DownloadGrant;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Review;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WebhookEvent;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create Super Admin staff user
    $this->adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $this->adminUser = User::factory()->create([
        'name' => 'Admin Controller',
        'email' => 'superadmin@example.com',
    ]);
    $this->adminUser->assignRole($this->adminRole);
});

it('allows super admin to load the admin dashboard and widgets', function () {
    $response = $this->actingAs($this->adminUser)->get('/admin');

    $response->assertOk();
    $response->assertSee('Admin Controller');

    // Test widgets data generation directly
    $revenueWidget = new \App\Filament\Widgets\RevenueChartWidget();
    expect($revenueWidget->getHeading())->toBe('Revenue Trend (Last 30 Days)');

    $statsWidget = new \App\Filament\Widgets\DashboardOverviewStatsWidget();
    expect($statsWidget)->not->toBeNull();
});

it('allows super admin to access all commerce & finance resources', function () {
    $this->actingAs($this->adminUser);

    $this->get('/admin/orders')->assertOk()->assertSee('Orders');
    $this->get('/admin/webhook-events')->assertOk()->assertSee('Transactions & Gateway Logs');
    $this->get('/admin/invoices')->assertOk()->assertSee('Invoices & Credit Notes');
    $this->get('/admin/coupons')->assertOk()->assertSee('Discounts & Coupons');
    $this->get('/admin/affiliates')->assertOk()->assertSee('Affiliate Program');
});

it('allows super admin to access all licensing & delivery resources', function () {
    $this->actingAs($this->adminUser);

    $this->get('/admin/licenses')->assertOk()->assertSee('License Keys');
    $this->get('/admin/license-activations')->assertOk()->assertSee('Device & Domain Activations');
    $this->get('/admin/download-grants')->assertOk()->assertSee('Download Grants & Logs');
});

it('allows super admin to access all customer & support resources', function () {
    $this->actingAs($this->adminUser);

    $this->get('/admin/customers')->assertOk()->assertSee('Customers');
    $this->get('/admin/support-tickets')->assertOk()->assertSee('Support Tickets');
    $this->get('/admin/reviews')->assertOk()->assertSee('Reviews & Ratings');
});

it('allows super admin to access system & security resources', function () {
    $this->actingAs($this->adminUser);

    $this->get('/admin/users')->assertOk()->assertSee('Administrators & Roles');
    $this->get('/admin/audit-logs')->assertOk()->assertSee('Audit Logs');
    $this->get('/admin/store-settings')->assertOk()->assertSee('Store & Infrastructure Settings');
});

it('restricts support tickets resource based on permissions', function () {
    // Staff user without support.manage_tickets permission
    $catalogRole = Role::firstOrCreate(['name' => 'Catalog Editor', 'guard_name' => 'web']);
    $staffUser = User::factory()->create(['email' => 'catalog@example.com']);
    $staffUser->assignRole($catalogRole);

    $response = $this->actingAs($staffUser)->get('/admin/support-tickets');
    $response->assertForbidden();
});

it('restricts review moderation resource based on permissions', function () {
    $financeRole = Role::firstOrCreate(['name' => 'Finance Manager', 'guard_name' => 'web']);
    $staffUser = User::factory()->create(['email' => 'finance@example.com']);
    $staffUser->assignRole($financeRole);

    $response = $this->actingAs($staffUser)->get('/admin/reviews');
    $response->assertForbidden();
});

it('restricts store settings page based on permissions', function () {
    $supportRole = Role::firstOrCreate(['name' => 'Support Agent', 'guard_name' => 'web']);
    $staffUser = User::factory()->create(['email' => 'support@example.com']);
    $staffUser->assignRole($supportRole);

    $response = $this->actingAs($staffUser)->get('/admin/store-settings');
    $response->assertForbidden();
});

it('executes the licensing state machine: suspend, reinstate, and revoke', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create();
    $license = License::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'status' => LicenseStatus::Active,
        'current_activations_count' => 1,
    ]);

    $activation = LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-node-alpha',
        'hostname' => 'node-alpha.example.com',
        'ip_address' => '10.0.0.1',
        'is_active' => true,
        'activated_at' => now(),
    ]);

    // 1. Suspend: Active -> Suspended
    $license->update(['status' => LicenseStatus::Suspended]);
    expect($license->fresh()->status)->toBe(LicenseStatus::Suspended);

    // 2. Reinstate: Suspended -> Active
    $license->update(['status' => LicenseStatus::Active]);
    expect($license->fresh()->status)->toBe(LicenseStatus::Active);

    // 3. Revoke: Active -> Revoked (and terminates activations)
    $license->update([
        'status' => LicenseStatus::Revoked,
        'current_activations_count' => 0,
    ]);
    $license->activations()->where('is_active', true)->update([
        'is_active' => false,
        'deactivated_at' => now(),
    ]);

    expect($license->fresh()->status)->toBe(LicenseStatus::Revoked);
    expect($activation->fresh()->is_active)->toBeFalse();
    expect($activation->fresh()->deactivated_at)->not->toBeNull();
});

it('resets download grant attempts by adding +5 attempts', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    $grant = DownloadGrant::create([
        'order_item_id' => $item->id,
        'customer_id' => $customer->id,
        'max_download_attempts' => 5,
        'download_count' => 5,
        'is_revoked' => false,
    ]);

    $grant->update([
        'max_download_attempts' => $grant->max_download_attempts + 5,
        'is_revoked' => false,
    ]);

    expect($grant->fresh()->max_download_attempts)->toBe(10);
    expect($grant->fresh()->isValid())->toBeTrue();
});

it('allows customer with paid order to submit review from PDP and sets status to pending', function () {
    $customer = Customer::factory()->create(['email' => 'buyer@example.com']);
    $product = Product::factory()->create([
        'title' => 'Cloud Cluster Toolkit',
        'slug' => 'cloud-cluster-toolkit',
    ]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Paid,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $customer->id,
    ])->post(route('products.reviews.store', $product->id), [
        'rating' => 5,
        'title' => 'Exceptional Developer Experience',
        'review_text' => 'Seamless setup and comprehensive documentation. Highly recommended!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'order_item_id' => $orderItem->id,
        'rating' => 5,
        'status' => ReviewStatus::Pending->value,
    ]);
});

it('forbids customer without a paid order from submitting a review', function () {
    $nonBuyer = Customer::factory()->create(['email' => 'nonbuyer@example.com']);
    $product = Product::factory()->create(['title' => 'API Proxy Server']);

    $response = $this->withSession([
        'guest_customer_id' => $nonBuyer->id,
    ])->post(route('products.reviews.store', $product->id), [
        'rating' => 5,
        'title' => 'Looks Good',
        'review_text' => 'I have not bought it yet but it looks nice.',
    ]);

    $response->assertSessionHasErrors('review_text');
    $this->assertDatabaseMissing('reviews', [
        'customer_id' => $nonBuyer->id,
    ]);
});

it('saves store settings to key-value settings table', function () {
    Setting::set('store_name', 'DevStore Global');
    Setting::set('default_currency', 'BDT');
    Setting::set('gateway_sslcommerz', true);

    expect(Setting::get('store_name'))->toBe('DevStore Global');
    expect(Setting::get('default_currency'))->toBe('BDT');
    expect(Setting::get('gateway_sslcommerz'))->toBe('1');
});
