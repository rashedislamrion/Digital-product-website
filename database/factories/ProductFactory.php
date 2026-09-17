<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->catchPhrase();

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->paragraph(2),
            'description_html' => '<h3>Features</h3><ul><li>Production-ready architecture</li><li>Automated unit and feature test coverage</li><li>Detailed documentation and examples</li></ul><p>'.fake()->paragraphs(3, true).'</p>',
            'visibility' => ProductVisibility::Published,
            'product_type' => fake()->randomElement(ProductType::cases()),
            'compatibility_metadata' => [
                'php_version' => '>=8.2',
                'framework' => 'Laravel 12.x / 13.x',
                'database' => 'MySQL 8.0+ / PostgreSQL 15+',
            ],
            'is_featured' => fake()->boolean(20),
        ];
    }
}
