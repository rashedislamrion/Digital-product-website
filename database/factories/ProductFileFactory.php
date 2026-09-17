<?php

namespace Database\Factories;

use App\Models\ProductFile;
use App\Models\ProductVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFileFactory extends Factory
{
    protected $model = ProductFile::class;

    public function definition(): array
    {
        $fileName = 'package-v'.fake()->numberBetween(1, 3).'.'.fake()->numberBetween(0, 9).'.zip';

        return [
            'product_version_id' => ProductVersion::factory(),
            'storage_disk' => 's3_secure',
            'storage_path' => 'releases/'.(string) Str::uuid().'/'.$fileName,
            'file_name' => $fileName,
            'file_size_bytes' => fake()->numberBetween(2_000_000, 50_000_000),
            'mime_type' => 'application/zip',
            'checksum_sha256' => hash('sha256', Str::random(32)),
            'is_scanned_safe' => true,
        ];
    }
}
