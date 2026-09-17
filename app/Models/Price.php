<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'license_tier_name',
        'max_activation_seats',
        'amount_minor',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_activation_seats' => 'integer',
            'amount_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Formatted decimal amount accessor (e.g., $49.00).
     */
    protected function amountFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->amount_minor / 100, 2).' '.$this->currency,
        );
    }
}
