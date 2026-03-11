<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Global KYC Pool Model
 * 
 * Represents shared KYC numbers (BVN/NIN) that all companies can use as fallback
 * when their own director KYC fails
 */
class GlobalKycPool extends Model
{
    use HasFactory;

    protected $table = 'global_kyc_pool';

    protected $fillable = [
        'kyc_type',
        'kyc_number',
        'is_active',
        'usage_count',
        'success_count',
        'failure_count',
        'last_used_at',
        'last_success_at',
        'blacklisted_until',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'last_used_at' => 'datetime',
        'last_success_at' => 'datetime',
        'blacklisted_until' => 'datetime',
    ];

    /**
     * Get usage logs for this KYC
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(GlobalKycUsageLog::class, 'global_kyc_id');
    }

    /**
     * Get recent successful usage logs
     */
    public function recentSuccesses(): HasMany
    {
        return $this->usageLogs()
            ->where('success', true)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get recent failure logs
     */
    public function recentFailures(): HasMany
    {
        return $this->usageLogs()
            ->where('success', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if KYC is currently blacklisted
     */
    public function isBlacklisted(): bool
    {
        return $this->blacklisted_until && $this->blacklisted_until->isFuture();
    }

    /**
     * Check if KYC is available for use
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->isBlacklisted();
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->usage_count === 0) {
            return 100.0; // New KYC, assume 100% success rate
        }
        
        return ($this->success_count / $this->usage_count) * 100;
    }

    /**
     * Scope: Get available KYC numbers
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('blacklisted_until')
                  ->orWhere('blacklisted_until', '<=', now());
            });
    }

    /**
     * Scope: Get by KYC type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('kyc_type', $type);
    }

    /**
     * Scope: Order by least used first (for fair distribution)
     */
    public function scopeLeastUsedFirst($query)
    {
        return $query->orderBy('usage_count', 'asc')
            ->orderBy('last_used_at', 'asc');
    }

    /**
     * Scope: Order by highest success rate first
     */
    public function scopeHighestSuccessFirst($query)
    {
        return $query->orderByRaw('(success_count / GREATEST(usage_count, 1)) DESC')
            ->orderBy('usage_count', 'asc');
    }
}