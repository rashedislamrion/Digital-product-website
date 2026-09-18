<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\ProductVersionStatus;
use App\Enums\ProductVisibility;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, HasUlids, Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'summary' => (string) $this->summary,
            'description_html' => strip_tags((string) $this->description_html),
            'compatibility_metadata' => json_encode($this->compatibility_metadata ?? []),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->visibility === ProductVisibility::Published;
    }

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'thumbnail_path',
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

    public function isPublished(): bool
    {
        return $this->visibility === ProductVisibility::Published;
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

    public function latestPublishedVersion(): HasOne
    {
        return $this->hasOne(ProductVersion::class)
            ->where('status', ProductVersionStatus::Published)
            ->latestOfMany('released_at');
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

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        $firstMedia = $this->media()->where('is_thumbnail', true)->first() ?? $this->media()->first();

        return $firstMedia ? Storage::disk($firstMedia->disk ?? 'public')->url($firstMedia->file_path) : null;
    }

    public function getPriceRangeAttribute(): string
    {
        $prices = $this->prices()->where('is_active', true)->orderBy('amount_minor')->get();

        if ($prices->isEmpty()) {
            return 'Free / Custom';
        }

        $min = $prices->first();
        $max = $prices->last();

        if ($min->id === $max->id) {
            return $min->amount_formatted;
        }

        return number_format($min->amount_minor / 100, 2).' - '.$max->amount_formatted;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('visibility', ProductVisibility::Published);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getLowestPriceAttribute(): ?Price
    {
        return $this->prices->where('is_active', true)->sortBy('amount_minor')->first();
    }

    public function getLatestPublishedVersionAttribute(): ?ProductVersion
    {
        return $this->versions
            ->where('status', ProductVersionStatus::Published)
            ->sortByDesc('released_at')
            ->first();
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->reviews->where('status', ReviewStatus::Published)->avg('rating');

        return $avg ? round((float) $avg, 1) : 4.9;
    }

    public function getCompatibilityListAttribute(): array
    {
        $metadata = $this->compatibility_metadata ?? [];
        $list = [];
        foreach ($metadata as $key => $value) {
            $list[] = is_string($key) && ! is_numeric($key) ? "{$key} {$value}" : $value;
        }

        return $list;
    }
}
