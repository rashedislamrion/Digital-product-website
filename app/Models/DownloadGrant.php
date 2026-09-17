<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DownloadGrant extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_item_id',
        'customer_id',
        'max_download_attempts',
        'download_count',
        'expires_at',
        'is_revoked',
    ];

    protected function casts(): array
    {
        return [
            'max_download_attempts' => 'integer',
            'download_count' => 'integer',
            'expires_at' => 'datetime',
            'is_revoked' => 'boolean',
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

    public function events(): HasMany
    {
        return $this->hasMany(DownloadEvent::class);
    }

    /**
     * Check if the grant is valid for download.
     */
    public function isValid(): bool
    {
        if ($this->is_revoked) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->download_count < $this->max_download_attempts;
    }
}
