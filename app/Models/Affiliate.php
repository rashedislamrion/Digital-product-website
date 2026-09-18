<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory, HasUlids;

    /**
     * Note: This is a placeholder model for the V2 Affiliate Program (research.md §4.1).
     */
    protected $fillable = [
        'name',
        'email',
        'referral_code',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
