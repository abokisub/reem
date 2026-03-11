<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Global KYC Usage Log Model
 * 
 * Tracks usage of global KYC numbers for analytics and monitoring
 */
class GlobalKycUsageLog extends Model
{
    use HasFactory;

    protected $table = 'global_kyc_usage_log';

    protected $fillable = [
        'global_kyc_id',
        'company_id',
        'virtual_account_id',
        'kyc_number',
        'kyc_type',
        'success',
        'error_message',
        'request_data'
    ];

    protected $casts = [
        'success' => 'boolean',
        'request_data' => 'array',
    ];

    /**
     * Get the global KYC that was used
     */
    public function globalKyc(): BelongsTo
    {
        return $this->belongsTo(GlobalKycPool::class, 'global_kyc_id');
    }

    /**
     * Get the company that used this KYC
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the virtual account that was created (if successful)
     */
    public function virtualAccount(): BelongsTo
    {
        return $this->belongsTo(VirtualAccount::class);
    }

    /**
     * Scope: Get successful usage logs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: Get failed usage logs
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: Get logs for specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get recent logs (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }
}