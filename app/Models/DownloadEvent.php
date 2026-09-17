<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadEvent extends Model
{
    use HasFactory, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'download_grant_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
        'bytes_transferred',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
            'bytes_transferred' => 'integer',
        ];
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(DownloadGrant::class, 'download_grant_id');
    }
}
