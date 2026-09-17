<?php

namespace Database\Factories;

use App\Enums\ProductVersionStatus;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVersionFactory extends Factory
{
    protected $model = ProductVersion::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'version_number' => fake()->semver(),
            'changelog_markdown' => "### Release Notes\n- Initial production release\n- Bug fixes and optimizations\n- Performance improvements",
            'min_runtime_version' => 'PHP 8.3',
            'status' => ProductVersionStatus::Published,
            'released_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }
}
