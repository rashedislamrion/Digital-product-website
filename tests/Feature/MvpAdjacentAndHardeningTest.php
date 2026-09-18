<?php

use App\Domain\Delivery\Services\PdfWatermarker;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Jobs\WatermarkPdfProductJob;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DownloadGrant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Models\WebhookEvent;
use App\Enums\WebhookEventStatus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

beforeEach(function () {
    Storage::fake('s3_secure');
    Storage::fake('public');
    Mail::fake();
});

test('paddle webhook verifies HMAC-SHA256 signature and grants entitlements on transaction.completed', function () {
    $secret = 'test_webhook_secret_key_123';
    Config::set('cashier.webhook_secret', $secret);

    $customer = Customer::factory()->create(['email' => 'buyer@example.com']);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Pending,
        'payment_gateway' => 'paddle',
    ]);

    $product = Product::factory()->create(['product_type' => ProductType::Software]);
    $version = ProductVersion::factory()->create(['product_id' => $product->id]);
    $price = Price::factory()->create(['product_id' => $product->id, 'max_activation_seats' => 3]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'price_id' => $price->id,
        'product_version_id' => $version->id,
    ]);

    $eventId = 'evt_test_'.uniqid();
    $payloadData = [
        'event_id' => $eventId,
        'event_type' => 'transaction.completed',
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'id' => 'txn_01h8g45a',
            'status' => 'completed',
            'custom_data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'details' => [
                'totals' => ['total' => 4900, 'tax' => 0],
            ],
        ],
    ];

    $rawPayload = json_encode($payloadData);
    $ts = time();
    $hash = hash_hmac('sha256', "{$ts}:{$rawPayload}", $secret);
    $signatureHeader = "ts={$ts};h1={$hash}";

    $response = $this->withHeaders([
        'Paddle-Signature' => $signatureHeader,
        'Content-Type' => 'application/json',
    ])->postJson('/payment/paddle/webhook', $payloadData);

    $response->assertOk()
        ->assertJson(['status' => 'success']);

    // Order should now be Paid
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    // Entitlement should be granted: download grant and license created
    $grant = DownloadGrant::where('order_item_id', $orderItem->id)->first();
    expect($grant)->not->toBeNull();
    expect($grant->max_download_attempts)->toBe(5);

    $license = $orderItem->fresh()->license;
    expect($license)->not->toBeNull();
    expect($license->max_activations)->toBe(3);

    // Webhook event recorded
    $webhookEvent = WebhookEvent::where('event_id', $eventId)->first();
    expect($webhookEvent)->not->toBeNull();
    expect($webhookEvent->status)->toBe(WebhookEventStatus::Processed);
});

test('paddle webhook rejects request when timestamp exceeds 5-second replay tolerance window', function () {
    $secret = 'test_webhook_secret_key_123';
    Config::set('cashier.webhook_secret', $secret);

    $payloadData = [
        'event_id' => 'evt_expired_test',
        'event_type' => 'transaction.completed',
        'data' => [],
    ];

    $rawPayload = json_encode($payloadData);
    // Skew timestamp by 10 seconds (exceeding 5 second threshold)
    $ts = time() - 10;
    $hash = hash_hmac('sha256', "{$ts}:{$rawPayload}", $secret);
    $signatureHeader = "ts={$ts};h1={$hash}";

    $response = $this->withHeaders([
        'Paddle-Signature' => $signatureHeader,
    ])->postJson('/payment/paddle/webhook', $payloadData);

    $response->assertStatus(403)
        ->assertJsonFragment(['error' => 'Replay tolerance window exceeded (skew: 10s, allowed: 5s)']);
});

test('paddle webhook rejects invalid HMAC-SHA256 signature hash', function () {
    $secret = 'test_webhook_secret_key_123';
    Config::set('cashier.webhook_secret', $secret);

    $payloadData = [
        'event_id' => 'evt_invalid_sig',
        'event_type' => 'transaction.completed',
        'data' => [],
    ];

    $ts = time();
    $signatureHeader = "ts={$ts};h1=invalid_hash_that_does_not_match";

    $response = $this->withHeaders([
        'Paddle-Signature' => $signatureHeader,
    ])->postJson('/payment/paddle/webhook', $payloadData);

    $response->assertStatus(403)
        ->assertJson(['error' => 'Invalid HMAC-SHA256 signature hash']);
});

test('paddle webhook handles replay events idempotently', function () {
    $secret = 'test_webhook_secret_key_123';
    Config::set('cashier.webhook_secret', $secret);

    $eventId = 'evt_idempotent_test';
    WebhookEvent::create([
        'gateway' => 'paddle',
        'event_id' => $eventId,
        'event_type' => 'transaction.completed',
        'raw_payload' => ['event_id' => $eventId],
        'status' => WebhookEventStatus::Processed,
        'processed_at' => now(),
    ]);

    $payloadData = [
        'event_id' => $eventId,
        'event_type' => 'transaction.completed',
        'data' => [],
    ];

    $rawPayload = json_encode($payloadData);
    $ts = time();
    $hash = hash_hmac('sha256', "{$ts}:{$rawPayload}", $secret);
    $signatureHeader = "ts={$ts};h1={$hash}";

    $response = $this->withHeaders([
        'Paddle-Signature' => $signatureHeader,
    ])->postJson('/payment/paddle/webhook', $payloadData);

    $response->assertOk()
        ->assertJson(['status' => 'already_processed']);
});

test('checkout processes paddle payment method selection', function () {
    $product = Product::factory()->create();
    $price = Price::factory()->create(['product_id' => $product->id, 'amount_minor' => 4900]);

    // Populate session cart
    $cartService = app(\App\Domain\Commerce\Services\CartService::class);
    $cartService->add($product->id, $price->id, 1);

    $response = $this->post(route('checkout.process'), [
        'email' => 'international@example.com',
        'country' => 'United States',
        'payment_method' => 'paddle',
    ]);

    $order = Order::where('payment_gateway', 'paddle')->first();
    expect($order)->not->toBeNull();
    expect($order->customer->email)->toBe('international@example.com');
    expect($order->status)->toBe(OrderStatus::Pending);

    $response->assertRedirect(route('orders.confirmation', [
        'order_number' => $order->order_number,
        'gateway' => 'paddle',
    ]));
});

test('scout search with database driver indexes product attributes and returns matches', function () {
    Config::set('scout.driver', 'database');

    $matchingProduct = Product::factory()->create([
        'title' => 'MicroSaaS Starter Kit for Laravel',
        'summary' => 'Production-ready boilerplate with billing and teams.',
        'visibility' => ProductVisibility::Published,
    ]);

    $nonMatchingProduct = Product::factory()->create([
        'title' => 'Python Data Science Notebooks',
        'summary' => 'Jupyter machine learning pipelines.',
        'visibility' => ProductVisibility::Published,
    ]);

    $draftProduct = Product::factory()->create([
        'title' => 'Draft Laravel MicroSaaS Extension',
        'visibility' => ProductVisibility::Draft,
    ]);

    // Test Model Search
    $results = Product::search('MicroSaaS')->get();

    expect($results->pluck('id'))->toContain($matchingProduct->id);
    expect($results->pluck('id'))->not->toContain($nonMatchingProduct->id);
});

test('storefront /search endpoint renders matching results and empty states', function () {
    Config::set('scout.driver', 'database');

    $product = Product::factory()->create([
        'title' => 'Alpine Jetpack Component Pack',
        'summary' => 'Modern reactive UI elements.',
        'visibility' => ProductVisibility::Published,
    ]);

    // Matching query
    $response = $this->get(route('search', ['q' => 'Alpine']));
    $response->assertOk()
        ->assertSee('Search Results for')
        ->assertSee('Alpine')
        ->assertSee('Alpine Jetpack Component Pack');

    // Non-matching query renders empty state
    $emptyResponse = $this->get(route('search', ['q' => 'NonExistentProductQuery999']));
    $emptyResponse->assertOk()
        ->assertSee('No products found matching')
        ->assertSee('NonExistentProductQuery999')
        ->assertSee('Suggested keywords:');
});

test('pdf watermarker stamps buyer email and order reference on pdf documents', function () {
    // Generate a valid 1-page test PDF
    $pdf = new Fpdi();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sample Ebook Chapter 1', 0, 1);
    $rawPdf = $pdf->Output('S');

    $watermarker = new PdfWatermarker();
    $watermarkedPdf = $watermarker->watermark($rawPdf, 'reader@example.com', 'ORD-202609-WTRMRK');

    expect($watermarkedPdf)->toBeString();
    expect(strlen($watermarkedPdf))->toBeGreaterThan(strlen($rawPdf));
    expect($watermarkedPdf)->toContain('reader@example.com');
    expect($watermarkedPdf)->toContain('ORD-202609-WTRMRK');
});

test('watermark job generates watermarked file for ebook products', function () {
    $customer = Customer::factory()->create(['email' => 'ebookbuyer@example.com']);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'ORD-EBK-100']);
    $product = Product::factory()->create(['product_type' => ProductType::Ebook]);
    $version = ProductVersion::factory()->create(['product_id' => $product->id]);
    $file = ProductFile::factory()->create([
        'product_version_id' => $version->id,
        'file_name' => 'handbook.pdf',
        'storage_disk' => 's3_secure',
        'storage_path' => 'releases/v1/handbook.pdf',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_version_id' => $version->id,
    ]);

    $grant = DownloadGrant::factory()->create([
        'order_item_id' => $orderItem->id,
        'customer_id' => $customer->id,
    ]);

    $job = new WatermarkPdfProductJob($grant->id);
    $job->handle(new PdfWatermarker());

    $expectedWatermarkedPath = "watermarked/{$grant->id}/handbook.pdf";
    expect(Storage::disk('s3_secure')->exists($expectedWatermarkedPath))->toBeTrue();
});

test('security headers middleware attaches OWASP headers to responses', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

test('public forms sanitize inputs via FormRequest classes', function () {
    // CheckoutRequest
    $checkoutRequest = new \App\Http\Requests\CheckoutRequest();
    $checkoutRequest->merge([
        'email' => '  USER@EXAMPLE.COM  ',
        'country' => '<b>Bangladesh</b>',
        'payment_method' => ' sslcommerz ',
    ]);
    $checkoutRequest->setContainer(app())->validateResolved();
    $validated = $checkoutRequest->validated();

    expect($validated['email'])->toBe('user@example.com');
    expect($validated['country'])->toBe('Bangladesh');
    expect($validated['payment_method'])->toBe('sslcommerz');

    // StoreReviewRequest
    $reviewRequest = new \App\Http\Requests\StoreReviewRequest();
    $reviewRequest->merge([
        'rating' => '5',
        'title' => '<h1>Great Theme</h1>',
        'review_text' => '<script>alert(1)</script>This is a solid production toolkit that saved our team weeks.',
    ]);
    $reviewRequest->setContainer(app())->validateResolved();
    $reviewValidated = $reviewRequest->validated();

    expect($reviewValidated['title'])->toBe('Great Theme');
    expect($reviewValidated['review_text'])->not->toContain('<script>');
    expect($reviewValidated['rating'])->toBe(5);
});
