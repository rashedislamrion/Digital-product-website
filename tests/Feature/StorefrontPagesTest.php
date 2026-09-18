<?php

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('renders the public homepage with hero stats, product shelves, and trust banner', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Production-Grade')
        ->assertSee('Software Starter Kits')
        ->assertSee('Secure Downloads')
        ->assertSee('Average Rating')
        ->assertSee('Published Releases')
        ->assertSee('Featured Releases')
        ->assertSee('Bestsellers')
        ->assertSee('Recent Updates')
        ->assertSee('Instant Fulfillment')
        ->assertSee('Checksum Verified')
        ->assertSee('30-Day Money-Back Guarantee')
        ->assertSee('SaaS Launchpad Pro');
});

it('renders the catalog page with product cards, sidebar filters, and sorting', function () {
    $response = $this->get('/products');

    $response->assertOk()
        ->assertSee('Developer Catalog')
        ->assertSee('Categories')
        ->assertSee('Product Type')
        ->assertSee('Price Range')
        ->assertSee('SaaS Launchpad Pro')
        ->assertSee('Sort by:');
});

it('filters catalog products by category slug', function () {
    $response = $this->get('/products?category[]=developer-tools-starter-kits');

    $response->assertOk()
        ->assertSee('SaaS Launchpad Pro')
        ->assertDontSee('Apex Admin Dashboard UI Kit');
});

it('filters catalog products by product type', function () {
    $response = $this->get('/products?product_type[]=theme');

    $response->assertOk()
        ->assertSee('Apex Admin Dashboard UI Kit')
        ->assertDontSee('API Gateway & Sentinel');
});

it('filters catalog products by price range', function () {
    // SaaS Launchpad Pro has lowest price $49.00 (4900 minor)
    // Apex Admin Dashboard has lowest price $29.00 (2900 minor)
    $response = $this->get('/products?min_price=40&max_price=60');

    $response->assertOk()
        ->assertSee('SaaS Launchpad Pro')
        ->assertDontSee('Apex Admin Dashboard UI Kit');
});

it('renders empty state when no products match filters', function () {
    $response = $this->get('/products?q=nonexistentkeywordthatmatchesnothing');

    $response->assertOk()
        ->assertSee('No products found')
        ->assertSee('Clear All Filters');
});

it('renders category page scoped to specific category', function () {
    $category = Category::where('slug', 'laravel-ecosystem-themes')->first();

    $response = $this->get("/categories/{$category->slug}");

    $response->assertOk()
        ->assertSee($category->name)
        ->assertSee('Apex Admin Dashboard UI Kit');
});

it('returns 404 for invalid category slug', function () {
    $this->get('/categories/non-existent-category-slug')->assertNotFound();
});

it('renders product detail page with license tiers, tabs, and related products', function () {
    $product = Product::where('slug', 'saas-launchpad-pro')->first();

    $response = $this->get("/products/{$product->slug}");

    $response->assertOk()
        ->assertSee($product->title)
        ->assertSee($product->summary)
        ->assertSee('Select Commercial License')
        ->assertSee('Single Application')
        ->assertSee('Team License')
        ->assertSee('Unlimited License')
        ->assertSee('Technical Specifications')
        ->assertSee('Changelog')
        ->assertSee('Support Policy')
        ->assertSee('Verified Reviews')
        ->assertSee('Frequently Paired');
});

it('returns 404 for non-existent product slug', function () {
    $this->get('/products/non-existent-product-slug')->assertNotFound();
});

it('does not display unpublished or draft products on public storefront', function () {
    $draft = Product::create([
        'category_id' => Category::first()->id,
        'title' => 'Secret Unreleased Software',
        'slug' => 'secret-unreleased-software',
        'summary' => 'In progress draft',
        'description_html' => '<p>Confidential</p>',
        'visibility' => ProductVisibility::Draft,
        'product_type' => ProductType::Software,
    ]);

    // Hidden from catalog
    $this->get('/products')->assertDontSee('Secret Unreleased Software');

    // 404 on direct detail URL
    $this->get("/products/{$draft->slug}")->assertNotFound();
});
