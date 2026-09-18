<?php

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\SupportTicketStatus;
use App\Models\Customer;
use App\Models\DownloadGrant;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\SupportTicket;
use App\Models\User;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        'email' => 'customer@example.com',
        'name' => 'Alice Dev',
    ]);
});

it('redirects unauthenticated visitor without guest session to magic link page', function () {
    $response = $this->get(route('customer.library'));

    $response->assertRedirect(route('magic-link.create'));
    $response->assertSessionHasErrors('email');
});

it('allows access via /library URL directly for authenticated customer user', function () {
    $user = User::factory()->create([
        'email' => 'customer@example.com',
    ]);
    $this->customer->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/library');

    $response->assertOk();
    $response->assertSee('My Digital Library');
    $response->assertSee('customer@example.com');
    $response->assertDontSee('Guest access session active:');
});

it('allows guest customer with active session to view digital library with guest notice', function () {
    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
        'guest_customer_email' => $this->customer->email,
    ])->get('/library');

    $response->assertOk();
    $response->assertSee('My Digital Library');
    $response->assertSee('customer@example.com');
    $response->assertSee('Guest access session active:');
    $response->assertSee(route('customer.set-password'));
});

it('renders purchased products with download grant quotas, latest version, and changelog link', function () {
    $product = Product::factory()->create([
        'title' => 'DevOps Starter Kit',
        'slug' => 'devops-starter-kit',
        'description_html' => '<p>DevOps Starter Kit description</p>',
    ]);
    $version = ProductVersion::factory()->create([
        'product_id' => $product->id,
        'version_number' => '2.1.0',
    ]);
    $price = Price::factory()->create([
        'product_id' => $product->id,
        'license_tier_name' => 'Pro License',
    ]);

    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
        'order_number' => 'ORD-TEST-001',
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_version_id' => $version->id,
        'price_id' => $price->id,
        'historical_product_title' => 'DevOps Starter Kit',
        'historical_tier_name' => 'Pro License',
        'unit_amount_minor' => 4900,
    ]);

    $grant = DownloadGrant::create([
        'order_item_id' => $item->id,
        'customer_id' => $this->customer->id,
        'max_download_attempts' => 5,
        'download_count' => 2,
        'expires_at' => null,
        'is_revoked' => false,
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get('/library');

    $response->assertOk();
    $response->assertSee('DevOps Starter Kit');
    $response->assertSee('Pro License');
    $response->assertSee('Current: v2.1.0');
    $response->assertSee('2/5 downloads');
    $response->assertSee('Download Latest');
    $response->assertSee(route('library.download', ['download_grant' => $grant->id]));
    $response->assertSee(route('products.show', 'devops-starter-kit').'#changelog');
});

it('renders software licenses and active installations tab with seat usage', function () {
    $product = Product::factory()->create([
        'title' => 'API Microservice Kit',
        'description_html' => '<p>API Microservice Kit description</p>',
    ]);

    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'historical_product_title' => 'API Microservice Kit',
        'historical_tier_name' => 'Agency Tier',
    ]);

    $license = License::factory()->create([
        'order_item_id' => $item->id,
        'customer_id' => $this->customer->id,
        'product_id' => $product->id,
        'license_key_masked' => 'PROD-XXXX-XXXX-XXXX-99AA',
        'status' => LicenseStatus::Active,
        'max_activations' => 3,
        'current_activations_count' => 1,
    ]);

    $activation = LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-node-macbook-pro',
        'hostname' => 'macbook-dev.local',
        'ip_address' => '127.0.0.1',
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get('/library');

    $response->assertOk();
    $response->assertSee('PROD-XXXX-XXXX-XXXX-99AA');
    $response->assertSee('macbook-dev.local');
    $response->assertSee('1 / 3 seats used');
    $response->assertSee('Deactivate this device');
});

it('allows customer to self-deactivate an active installation and decrements seat count', function () {
    $license = License::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => LicenseStatus::Active,
        'max_activations' => 5,
        'current_activations_count' => 2,
    ]);

    $activation = LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-server-01',
        'hostname' => 'prod-worker-1.local',
        'ip_address' => '10.0.0.1',
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->postJson(route('customer.deactivate', $activation->id));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Installation deactivated successfully.',
        'activations_remaining' => 4,
    ]);

    $activation->refresh();
    expect($activation->is_active)->toBeFalse()
        ->and($activation->deactivated_at)->not->toBeNull();

    $license->refresh();
    expect($license->current_activations_count)->toBe(1);
});

it('forbids customer from deactivating an installation belonging to another customer', function () {
    $otherCustomer = Customer::factory()->create([
        'email' => 'other@example.com',
    ]);

    $license = License::factory()->create([
        'customer_id' => $otherCustomer->id,
        'status' => LicenseStatus::Active,
        'max_activations' => 2,
        'current_activations_count' => 1,
    ]);

    $activation = LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-other-server',
        'hostname' => 'other-server.local',
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->postJson(route('customer.deactivate', $activation->id));

    $response->assertForbidden();

    $activation->refresh();
    expect($activation->is_active)->toBeTrue();
});

it('handles already deactivated installation gracefully', function () {
    $license = License::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => LicenseStatus::Active,
        'max_activations' => 3,
        'current_activations_count' => 0,
    ]);

    $activation = LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-old-server',
        'hostname' => 'old-server.local',
        'is_active' => false,
        'activated_at' => now()->subDays(10),
        'deactivated_at' => now()->subDay(),
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->postJson(route('customer.deactivate', $activation->id));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Already deactivated.',
    ]);
});

it('renders PDF invoice download link and generates a valid PDF stream for customer order', function () {
    $product = Product::factory()->create([
        'title' => 'SaaS Starter Kit',
        'description_html' => '<p>SaaS Starter Kit description</p>',
    ]);

    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'order_number' => 'ORD-INV-TEST-999',
        'status' => OrderStatus::Paid,
        'total_minor' => 7900,
        'payment_gateway' => 'sslcommerz',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'historical_product_title' => 'SaaS Starter Kit',
        'historical_tier_name' => 'Agency License',
        'unit_amount_minor' => 7900,
    ]);

    // Check link renders on the dashboard
    $dashboardResponse = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get('/library');

    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee(route('customer.orders.invoice', $order->id));
    $dashboardResponse->assertSee('PDF Invoice');

    // Test downloading the invoice PDF
    $invoiceResponse = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get(route('customer.orders.invoice', $order->id));

    $invoiceResponse->assertOk();
    $invoiceResponse->assertHeader('Content-Type', 'application/pdf');
    expect($invoiceResponse->headers->get('Content-Disposition'))->toContain('invoice-ORD-INV-TEST-999.pdf');
});

it('prevents customer from downloading PDF invoice belonging to another customer', function () {
    $otherCustomer = Customer::factory()->create([
        'email' => 'stranger@example.com',
    ]);

    $otherOrder = Order::factory()->create([
        'customer_id' => $otherCustomer->id,
        'order_number' => 'ORD-STRANGER-111',
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get(route('customer.orders.invoice', $otherOrder->id));

    $response->assertForbidden();
});

it('allows customer to submit support ticket linked to an order and license', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::Paid,
    ]);

    $license = License::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => LicenseStatus::Active,
    ]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->post(route('customer.support-tickets.store'), [
        'subject' => 'Issue deploying on DigitalOcean',
        'message' => 'Having difficulty with SSL certificate provisioning.',
        'order_id' => $order->id,
        'license_id' => $license->id,
    ]);

    $response->assertRedirect(route('customer.library', ['tab' => 'support']));
    $response->assertSessionHas('status');

    $ticket = SupportTicket::where('customer_id', $this->customer->id)->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket->subject)->toBe('Issue deploying on DigitalOcean')
        ->and($ticket->message)->toBe('Having difficulty with SSL certificate provisioning.')
        ->and($ticket->order_id)->toBe($order->id)
        ->and($ticket->license_id)->toBe($license->id)
        ->and($ticket->status)->toBe(SupportTicketStatus::Open);
});

it('rejects support ticket if order does not belong to customer', function () {
    $stranger = Customer::factory()->create(['email' => 'stranger@test.com']);
    $strangerOrder = Order::factory()->create(['customer_id' => $stranger->id]);

    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->post(route('customer.support-tickets.store'), [
        'subject' => 'Hacking attempt',
        'message' => 'Linking stranger order',
        'order_id' => $strangerOrder->id,
    ]);

    $response->assertSessionHasErrors('order_id');
    expect(SupportTicket::count())->toBe(0);
});

it('renders empty state cleanly when customer has no purchases yet', function () {
    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get('/library');

    $response->assertOk();
    $response->assertSee('No purchases yet');
    $response->assertSee('No software licenses');
    $response->assertSee('No orders yet');
    $response->assertSee('No support tickets submitted yet.');
});

it('confirms all four tabs show correct real data for customer with full history', function () {
    // 1. Create paid order with software item, version, and download grant
    $product = Product::factory()->create([
        'title' => 'Real SaaS Monolith',
        'slug' => 'real-saas-monolith',
        'description_html' => '<p>Real SaaS Monolith description</p>',
    ]);
    $version = ProductVersion::factory()->create([
        'product_id' => $product->id,
        'version_number' => '3.0.0',
    ]);
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'order_number' => 'ORD-REAL-2026',
        'status' => OrderStatus::Paid,
        'payment_gateway' => 'sslcommerz',
        'total_minor' => 12900,
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_version_id' => $version->id,
        'historical_product_title' => 'Real SaaS Monolith',
        'historical_tier_name' => 'Extended Commercial',
        'unit_amount_minor' => 12900,
    ]);
    $grant = DownloadGrant::create([
        'order_item_id' => $item->id,
        'customer_id' => $this->customer->id,
        'max_download_attempts' => 5,
        'download_count' => 1,
        'expires_at' => null,
        'is_revoked' => false,
    ]);

    // 2. Create license with active installation
    $license = License::factory()->create([
        'order_item_id' => $item->id,
        'customer_id' => $this->customer->id,
        'product_id' => $product->id,
        'license_key_masked' => 'PROD-XXXX-XXXX-XXXX-ABCD',
        'status' => LicenseStatus::Active,
        'max_activations' => 5,
        'current_activations_count' => 1,
    ]);
    LicenseActivation::create([
        'license_id' => $license->id,
        'instance_fingerprint' => 'fp-production-cluster-01',
        'hostname' => 'prod-cluster.cloud.com',
        'ip_address' => '192.168.1.50',
        'is_active' => true,
        'activated_at' => now()->subDays(2),
    ]);

    // 3. Create existing support ticket
    SupportTicket::create([
        'customer_id' => $this->customer->id,
        'order_id' => $order->id,
        'license_id' => $license->id,
        'subject' => 'Docker Compose setup query',
        'message' => 'How do I configure the Redis cache cluster?',
        'status' => SupportTicketStatus::Open,
    ]);

    // 4. Request /library as customer
    $response = $this->withSession([
        'guest_customer_id' => $this->customer->id,
    ])->get('/library');

    $response->assertOk();

    // Tab 1 Assertions (My Products)
    $response->assertSee('Real SaaS Monolith');
    $response->assertSee('Extended Commercial');
    $response->assertSee('Current: v3.0.0');
    $response->assertSee('1/5 downloads');
    $response->assertSee('Download Latest');
    $response->assertSee(route('library.download', ['download_grant' => $grant->id]));
    $response->assertSee(route('products.show', 'real-saas-monolith').'#changelog');

    // Tab 2 Assertions (License Keys)
    $response->assertSee('PROD-XXXX-XXXX-XXXX-ABCD');
    $response->assertSee('1 / 5 seats used');
    $response->assertSee('prod-cluster.cloud.com');
    $response->assertSee('Deactivate this device');

    // Tab 3 Assertions (Order History & Invoices)
    $response->assertSee('ORD-REAL-2026');
    $response->assertSee('$129.00');
    $response->assertSee('SSLCOMMERZ');
    $response->assertSee(route('customer.orders.invoice', $order->id));
    $response->assertSee('PDF Invoice');

    // Tab 4 Assertions (Support Desk)
    $response->assertSee('Docker Compose setup query');
    $response->assertSee('How do I configure the Redis cache cluster?');
    $response->assertSee(route('customer.support-tickets.store'));
});

