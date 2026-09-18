<?php

namespace Database\Seeders;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Enums\ReviewStatus;
use App\Enums\WebhookEventStatus;
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
use App\Models\ProductFile;
use App\Models\ProductVersion;
use App\Models\Review;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed administrative roles, permissions, and staff test users
        $this->call([
            RolePermissionSeeder::class,
        ]);

        $adminUser = User::where('email', 'admin@example.com')->first();

        // 2. Create Categories
        $categoriesData = [
            [
                'name' => 'Developer Tools & Starter Kits',
                'slug' => 'developer-tools-starter-kits',
                'is_visible' => true,
            ],
            [
                'name' => 'Laravel Ecosystem Themes',
                'slug' => 'laravel-ecosystem-themes',
                'is_visible' => true,
            ],
            [
                'name' => 'APIs & Microservice Scripts',
                'slug' => 'apis-microservice-scripts',
                'is_visible' => true,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $categories[$data['slug']] = Category::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        // 3. Create 10 Sample Products
        $productsData = [
            [
                'category_slug' => 'developer-tools-starter-kits',
                'title' => 'SaaS Launchpad Pro',
                'slug' => 'saas-launchpad-pro',
                'summary' => 'Production-grade multi-tenant SaaS skeleton with billing, teams, and API scaffolding.',
                'product_type' => ProductType::Software,
                'is_featured' => true,
                'prices' => [
                    ['tier' => 'Single Application', 'seats' => 1, 'amount' => 4900],
                    ['tier' => 'Team License', 'seats' => 5, 'amount' => 9900],
                    ['tier' => 'Unlimited License', 'seats' => 999999, 'amount' => 24900],
                ],
                'versions' => [
                    ['version' => '1.0.0', 'status' => ProductVersionStatus::Published, 'safe' => true],
                    ['version' => '2.1.0', 'status' => ProductVersionStatus::Published, 'safe' => true],
                    ['version' => '2.2.0-rc1', 'status' => ProductVersionStatus::Quarantined, 'safe' => true], // Verified safe, ready for publish action demo
                    ['version' => '2.3.0-dev', 'status' => ProductVersionStatus::Quarantined, 'safe' => false], // Quarantined, awaiting scan demo
                ],
            ],
            [
                'category_slug' => 'apis-microservice-scripts',
                'title' => 'API Gateway & Sentinel',
                'slug' => 'api-gateway-sentinel',
                'summary' => 'High-throughput reverse proxy, HMAC signature verification, and distributed token bucket rate limiter.',
                'product_type' => ProductType::Software,
                'is_featured' => true,
                'prices' => [
                    ['tier' => 'Standard Node', 'seats' => 1, 'amount' => 3900],
                    ['tier' => 'Cluster License', 'seats' => 10, 'amount' => 12900],
                ],
                'versions' => ['1.2.0'],
            ],
            [
                'category_slug' => 'laravel-ecosystem-themes',
                'title' => 'Apex Admin Dashboard UI Kit',
                'slug' => 'apex-admin-dashboard-ui-kit',
                'summary' => 'Minimalist dark/light mode Tailwind CSS admin theme with 40+ pre-built Blade components.',
                'product_type' => ProductType::Theme,
                'is_featured' => true,
                'prices' => [
                    ['tier' => 'Single Project', 'seats' => 1, 'amount' => 2900],
                    ['tier' => 'Commercial Redistribution', 'seats' => 999999, 'amount' => 14900],
                ],
                'versions' => ['1.0.0', '1.4.2'],
            ],
            [
                'category_slug' => 'developer-tools-starter-kits',
                'title' => 'Cloud Storage Sync Engine',
                'slug' => 'cloud-storage-sync-engine',
                'summary' => 'Multi-provider file orchestrator supporting AWS S3, Cloudflare R2, and DigitalOcean Spaces.',
                'product_type' => ProductType::Software,
                'is_featured' => false,
                'prices' => [
                    ['tier' => 'Single Site', 'seats' => 1, 'amount' => 3500],
                    ['tier' => 'Unlimited Sites', 'seats' => 999999, 'amount' => 9900],
                ],
                'versions' => ['1.0.1'],
            ],
            [
                'category_slug' => 'apis-microservice-scripts',
                'title' => 'Payment Orchestration Hub',
                'slug' => 'payment-orchestration-hub',
                'summary' => 'Dual-track payment gateway adapter integrating SSLCOMMERZ, bKash, Paddle, and Stripe webhooks.',
                'product_type' => ProductType::Software,
                'is_featured' => true,
                'prices' => [
                    ['tier' => 'Single Merchant', 'seats' => 1, 'amount' => 5900],
                    ['tier' => 'Agency License', 'seats' => 25, 'amount' => 17900],
                ],
                'versions' => ['1.0.0', '1.3.0'],
            ],
            [
                'category_slug' => 'laravel-ecosystem-themes',
                'title' => 'CartFlow Digital Storefront Theme',
                'slug' => 'cartflow-digital-storefront-theme',
                'summary' => 'High-conversion, single-seller digital commerce storefront theme built with Tailwind CSS.',
                'product_type' => ProductType::Theme,
                'is_featured' => false,
                'prices' => [
                    ['tier' => 'Regular License', 'seats' => 1, 'amount' => 3900],
                    ['tier' => 'Extended License', 'seats' => 999999, 'amount' => 19900],
                ],
                'versions' => ['2.0.0'],
            ],
            [
                'category_slug' => 'developer-tools-starter-kits',
                'title' => 'Multi-Tenant DB Partitioning Engine',
                'slug' => 'multi-tenant-db-partitioning-engine',
                'summary' => 'Automated schema separation and tenant connection switching for high-compliance SaaS workloads.',
                'product_type' => ProductType::Software,
                'is_featured' => false,
                'prices' => [
                    ['tier' => 'Developer License', 'seats' => 1, 'amount' => 4500],
                    ['tier' => 'Enterprise License', 'seats' => 999999, 'amount' => 18900],
                ],
                'versions' => ['1.1.0'],
            ],
            [
                'category_slug' => 'apis-microservice-scripts',
                'title' => 'Cryptographic License Key Sentinel',
                'slug' => 'cryptographic-license-key-sentinel',
                'summary' => 'Hardware fingerprinting, offline token signing, and automated seat activation validation server.',
                'product_type' => ProductType::Software,
                'is_featured' => true,
                'prices' => [
                    ['tier' => '1 Server Instance', 'seats' => 1, 'amount' => 6900],
                    ['tier' => 'Unlimited Instances', 'seats' => 999999, 'amount' => 22900],
                ],
                'versions' => ['1.0.0', '2.0.0'],
            ],
            [
                'category_slug' => 'laravel-ecosystem-themes',
                'title' => 'Zenith Tech Documentation Theme',
                'slug' => 'zenith-tech-documentation-theme',
                'summary' => 'Keyboard-navigable developer documentation theme with live search, code lightboxes, and API tables.',
                'product_type' => ProductType::Theme,
                'is_featured' => false,
                'prices' => [
                    ['tier' => 'Single Domain', 'seats' => 1, 'amount' => 2500],
                    ['tier' => 'Unlimited Domains', 'seats' => 999999, 'amount' => 8900],
                ],
                'versions' => ['1.0.0'],
            ],
            [
                'category_slug' => 'developer-tools-starter-kits',
                'title' => 'Real-Time Telemetry & Pulse SDK',
                'slug' => 'real-time-telemetry-pulse-sdk',
                'summary' => 'In-memory metrics aggregation, slow query detection, and queue latency monitor for Laravel.',
                'product_type' => ProductType::Software,
                'is_featured' => false,
                'prices' => [
                    ['tier' => 'Single Application', 'seats' => 1, 'amount' => 3200],
                    ['tier' => 'Team Application', 'seats' => 5, 'amount' => 7900],
                ],
                'versions' => ['1.0.0', '1.1.2'],
            ],
        ];

        $seededProducts = [];

        foreach ($productsData as $pData) {
            $category = $categories[$pData['category_slug']];

            $product = Product::firstOrCreate(
                ['slug' => $pData['slug']],
                [
                    'category_id' => $category->id,
                    'title' => $pData['title'],
                    'summary' => $pData['summary'],
                    'description_html' => '<h3>Architectural Highlights</h3><p>'.$pData['summary'].'</p><p>Includes full source code, test suites, and lifetime release updates.</p>',
                    'visibility' => ProductVisibility::Published,
                    'product_type' => $pData['product_type'],
                    'compatibility_metadata' => [
                        'php' => '>=8.2',
                        'framework' => 'Laravel 12.x / 13.x',
                        'database' => 'MySQL 8.0+ / PostgreSQL 15+',
                    ],
                    'is_featured' => $pData['is_featured'],
                ]
            );

            // Seed Prices
            foreach ($pData['prices'] as $priceData) {
                Price::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'license_tier_name' => $priceData['tier'],
                    ],
                    [
                        'max_activation_seats' => $priceData['seats'],
                        'amount_minor' => $priceData['amount'],
                        'currency' => 'USD',
                        'is_active' => true,
                    ]
                );
            }

            // Seed Versions & Files with Real Physical ZIP Archives on s3_secure Disk
            foreach ($pData['versions'] as $ver) {
                $verNumber = is_array($ver) ? $ver['version'] : $ver;
                $status = is_array($ver) ? ($ver['status'] ?? ProductVersionStatus::Published) : ProductVersionStatus::Published;
                $isSafe = is_array($ver) ? ($ver['safe'] ?? true) : true;
                $releasedAt = $status === ProductVersionStatus::Published ? now()->subDays(rand(5, 45)) : null;

                $version = ProductVersion::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'version_number' => $verNumber,
                    ],
                    [
                        'changelog_markdown' => "### Release v{$verNumber}\n- Enhanced architecture stability\n- Security updates and memory tuning\n- Framework 13 compatibility updates",
                        'min_runtime_version' => 'PHP 8.3',
                        'status' => $status,
                        'released_at' => $releasedAt,
                    ]
                );

                $fileName = "{$product->slug}-v{$verNumber}.zip";
                $zipBinary = $this->generateDummyZip($product->slug, $product->title, $verNumber);
                $storageDir = 'releases/'.(string) Str::uuid();
                $storagePath = "{$storageDir}/{$fileName}";

                // Write actual binary to s3_secure disk
                Storage::disk('s3_secure')->put($storagePath, $zipBinary);
                $checksum = hash('sha256', $zipBinary);
                $sizeBytes = strlen($zipBinary);

                ProductFile::firstOrCreate(
                    [
                        'product_version_id' => $version->id,
                        'file_name' => $fileName,
                    ],
                    [
                        'storage_disk' => 's3_secure',
                        'storage_path' => $storagePath,
                        'file_size_bytes' => $sizeBytes,
                        'mime_type' => 'application/zip',
                        'checksum_sha256' => $checksum,
                        'is_scanned_safe' => $isSafe,
                    ]
                );
            }

            $seededProducts[] = $product;
        }

        // 4. Create 5 Customers
        $customersData = [
            ['name' => 'Rashed Islam', 'email' => 'rashed@example.com', 'country_code' => 'BD', 'tax' => null],
            ['name' => 'Johnathan Davis', 'email' => 'john.davis@example.com', 'country_code' => 'US', 'tax' => null],
            ['name' => 'Sarah Smith', 'email' => 'sarah.smith@example.com', 'country_code' => 'GB', 'tax' => 'GB987654321'],
            ['name' => 'Alex Müller', 'email' => 'alex.muller@example.com', 'country_code' => 'DE', 'tax' => 'DE123456789'],
            ['name' => 'Tanvir Ahmed', 'email' => 'tanvir.ahmed@example.com', 'country_code' => 'BD', 'tax' => null],
        ];

        $seededCustomers = [];
        foreach ($customersData as $cData) {
            $seededCustomers[] = Customer::firstOrCreate(
                ['email' => $cData['email']],
                [
                    'name' => $cData['name'],
                    'country_code' => $cData['country_code'],
                    'tax_identifier' => $cData['tax'],
                ]
            );
        }

        // 5. Create Orders, Order Items, Download Grants, and Licenses
        $gateways = ['sslcommerz', 'bkash', 'paddle', 'stripe'];

        foreach ($seededCustomers as $index => $customer) {
            $product = $seededProducts[$index % count($seededProducts)];
            $price = $product->prices()->first();
            $version = $product->latestVersion;

            $orderNumber = 'ORD-'.now()->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $subtotal = $price->amount_minor;
            $tax = (int) round($subtotal * 0.05);
            $total = $subtotal + $tax;

            $order = Order::firstOrCreate(
                ['order_number' => $orderNumber],
                [
                    'customer_id' => $customer->id,
                    'status' => OrderStatus::Paid,
                    'currency' => 'USD',
                    'subtotal_minor' => $subtotal,
                    'discount_minor' => 0,
                    'tax_minor' => $tax,
                    'total_minor' => $total,
                    'payment_gateway' => $gateways[$index % count($gateways)],
                ]
            );

            $orderItem = OrderItem::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ],
                [
                    'product_version_id' => $version?->id,
                    'price_id' => $price->id,
                    'historical_product_title' => $product->title,
                    'historical_tier_name' => $price->license_tier_name,
                    'unit_amount_minor' => $subtotal,
                ]
            );

            // Download Grant
            $grant = DownloadGrant::firstOrCreate(
                [
                    'order_item_id' => $orderItem->id,
                    'customer_id' => $customer->id,
                ],
                [
                    'max_download_attempts' => 5,
                    'download_count' => 1,
                    'expires_at' => now()->addYear(),
                    'is_revoked' => false,
                ]
            );

            // Download Event
            DownloadEvent::firstOrCreate(
                [
                    'download_grant_id' => $grant->id,
                    'ip_address' => '103.145.231.14',
                ],
                [
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0.0.0 Safari/537.36',
                    'downloaded_at' => now()->subHours(rand(1, 24)),
                    'bytes_transferred' => 12_450_000,
                ]
            );

            // If software, seed License and Activation
            if ($product->product_type === ProductType::Software) {
                $rawKey = 'MKT-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
                $keyHash = hash('sha256', $rawKey);
                $maskedKey = 'MKT-XXXX-XXXX-'.substr($rawKey, -4);

                $license = License::firstOrCreate(
                    [
                        'order_item_id' => $orderItem->id,
                        'customer_id' => $customer->id,
                    ],
                    [
                        'product_id' => $product->id,
                        'license_key_hash' => $keyHash,
                        'license_key_masked' => $maskedKey,
                        'status' => LicenseStatus::Active,
                        'max_activations' => $price->max_activation_seats,
                        'current_activations_count' => 1,
                        'valid_until' => now()->addYear(),
                    ]
                );

                LicenseActivation::firstOrCreate(
                    [
                        'license_id' => $license->id,
                        'hostname' => "app.{$customer->country_code}.clientdomain.com",
                    ],
                    [
                        'instance_fingerprint' => hash('sha256', "machine-{$customer->id}-{$index}"),
                        'ip_address' => '103.145.231.14',
                        'is_active' => true,
                        'activated_at' => now()->subHours(12),
                        'deactivated_at' => null,
                    ]
                );
            }

            // Seed a review for the first 3 customers
            if ($index < 3) {
                Review::firstOrCreate(
                    ['order_item_id' => $orderItem->id],
                    [
                        'product_id' => $product->id,
                        'customer_id' => $customer->id,
                        'rating' => 5,
                        'title' => 'Exceptional codebase quality!',
                        'review_text' => 'The modular domain structure, zero-friction delivery, and test coverage saved our team weeks of engineering time.',
                        'status' => ReviewStatus::Published,
                        'merchant_reply' => 'Thank you so much! Feel free to reach out if you need priority support.',
                        'replied_at' => now(),
                    ]
                );
            }
        }

        // 6. Seed Sample Webhook Events
        WebhookEvent::firstOrCreate(
            [
                'gateway' => 'paddle',
                'event_id' => 'evt_paddle_01918a22bc71',
            ],
            [
                'event_type' => 'transaction.completed',
                'raw_payload' => [
                    'event_id' => 'evt_paddle_01918a22bc71',
                    'event_type' => 'transaction.completed',
                    'data' => [
                        'id' => 'txn_01918a22bc71',
                        'status' => 'completed',
                        'currency_code' => 'USD',
                        'details' => ['totals' => ['total' => 4900]],
                    ],
                ],
                'status' => WebhookEventStatus::Processed,
                'error_message' => null,
                'processed_at' => now()->subHours(2),
            ]
        );

        WebhookEvent::firstOrCreate(
            [
                'gateway' => 'sslcommerz',
                'event_id' => 'ssl_val_20260917_0042',
            ],
            [
                'event_type' => 'VALIDATED',
                'raw_payload' => [
                    'val_id' => 'ssl_val_20260917_0042',
                    'status' => 'VALID',
                    'tran_id' => 'ORD-202609-0001',
                    'amount' => '5145.00',
                    'currency' => 'BDT',
                ],
                'status' => WebhookEventStatus::Processed,
                'error_message' => null,
                'processed_at' => now()->subHour(),
            ]
        );

        // 7. Seed Sample Audit Log
        AuditLog::firstOrCreate(
            [
                'action' => 'catalog.seed',
                'target_type' => 'App\\Models\\Product',
                'target_id' => $seededProducts[0]->id,
            ],
            [
                'user_id' => $adminUser?->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Console Seeder CLI',
                'metadata_before' => null,
                'metadata_after' => ['seeded_count' => count($seededProducts)],
                'created_at' => now(),
            ]
        );

        // 8. Seed Standard Launch & Promo Coupons
        Coupon::firstOrCreate(
            ['code' => 'LAUNCH30'],
            [
                'discount_type' => 'percent',
                'discount_value' => 30, // 30% discount
                'currency' => 'USD',
                'min_order_amount_minor' => 2000, // $20.00 min spend
                'max_uses' => 500,
                'times_used' => 14,
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'DEV10'],
            [
                'discount_type' => 'fixed',
                'discount_value' => 1000, // $10.00 off
                'currency' => 'USD',
                'min_order_amount_minor' => 3000, // $30.00 min spend
                'max_uses' => 200,
                'times_used' => 5,
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ]
        );
    }

    protected function generateDummyZip(string $productSlug, string $productTitle, string $version): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'release_zip_');
        $zip = new ZipArchive;
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('README.md', "# {$productTitle} v{$version}\n\nThank you for licensing {$productTitle}. This package includes production-ready source code, tests, and documentation.");
            $zip->addFromString('manifest.json', (string) json_encode([
                'product' => $productSlug,
                'version' => $version,
                'created_at' => now()->toIso8601String(),
                'compatibility' => [
                    'php' => '>=8.2',
                    'framework' => 'Laravel 13.x',
                ],
            ], JSON_PRETTY_PRINT));
            $zip->addFromString('src/index.php', "<?php\n\n// {$productTitle} v{$version} entrypoint\necho '{$productTitle} initialized successfully.';\n");
            $zip->close();
        }

        $contents = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $contents;
    }
}
