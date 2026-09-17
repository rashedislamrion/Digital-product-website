<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFile extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_version_id',
        'storage_disk',
        'storage_path',
        'file_name',
        'file_size_bytes',
        'mime_type',
        'checksum_sha256',
        'is_scanned_safe',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'is_scanned_safe' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'product_version_id');
    }
}
