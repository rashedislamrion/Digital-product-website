<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'currency',
        'subtotal_minor',
        'discount_minor',
        'tax_minor',
        'total_minor',
        'payment_gateway',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Formatted total amount accessor.
     */
    protected function totalFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->total_minor / 100, 2).' '.$this->currency,
        );
    }

    /**
     * Formatted subtotal amount accessor.
     */
    protected function subtotalFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->subtotal_minor / 100, 2).' '.$this->currency,
        );
    }

    /**
     * Formatted discount amount accessor.
     */
    protected function discountFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->discount_minor / 100, 2).' '.$this->currency,
        );
    }

    /**
     * Formatted tax amount accessor.
     */
    protected function taxFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->tax_minor / 100, 2).' '.$this->currency,
        );
    }
}
