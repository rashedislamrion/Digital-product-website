<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'currency',
        'min_order_amount_minor',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_order_amount_minor' => 'integer',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check whether this coupon is currently valid for the given order subtotal in minor units.
     */
    public function isValidForAmount(int $subtotalMinor): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        if ($this->min_order_amount_minor !== null && $subtotalMinor < $this->min_order_amount_minor) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount in minor units for a given subtotal.
     */
    public function calculateDiscount(int $subtotalMinor): int
    {
        if (! $this->isValidForAmount($subtotalMinor)) {
            return 0;
        }

        if ($this->discount_type === 'percent') {
            $discount = (int) round(($subtotalMinor * $this->discount_value) / 100);

            return min($discount, $subtotalMinor);
        }

        // Fixed discount in minor units
        return min($this->discount_value, $subtotalMinor);
    }

    /**
     * Increment usage counter.
     */
    public function recordUsage(): void
    {
        $this->increment('times_used');
    }
}
