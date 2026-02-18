<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [
        'name',
        'code',
        'palmpay_code',
        'active',
        'supports_transfers',
        'supports_account_verification',
    ];

    protected $casts = [
        'active' => 'boolean',
        'supports_transfers' => 'boolean',
        'supports_account_verification' => 'boolean',
    ];

    /**
     * Scope for active banks
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for banks that support transfers
     */
    public function scopeSupportsTransfers($query)
    {
        return $query->where('supports_transfers', true);
    }
}