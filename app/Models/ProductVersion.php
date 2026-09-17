<?php

namespace App\Models;

use App\Enums\ProductVersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVersion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'version_number',
        'changelog_markdown',
        'min_runtime_version',
        'status',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductVersionStatus::class,
            'released_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
    }
}
