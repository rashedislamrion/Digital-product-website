<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_item_id',
        'customer_id',
        'product_id',
        'license_key_hash',
        'license_key_masked',
        'status',
        'max_activations',
        'current_activations_count',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'max_activations' => 'integer',
            'current_activations_count' => 'integer',
            'valid_until' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    /**
     * Check if more activations can be granted.
     */
    public function canActivate(): bool
    {
        if (! in_array($this->status, [LicenseStatus::Active, LicenseStatus::Issued])) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return $this->current_activations_count < $this->max_activations;
    }
}
