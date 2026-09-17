<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'description_html',
        'visibility',
        'product_type',
        'compatibility_metadata',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => ProductVisibility::class,
            'product_type' => ProductType::class,
            'compatibility_metadata' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProductVersion::class)->latestOfMany('released_at');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
