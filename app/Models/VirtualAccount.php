<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VirtualAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'account_id',
        'company_id',
        'company_user_id',
        'user_id',
        'bank_code',
        'account_type',
        'amount',
        'provider',
        'provider_reference',
        'account_number',
        'bank_name',
        'account_name',
        'palmpay_account_number',
        'palmpay_account_name',
        'palmpay_bank_name',
        'palmpay_customer_id',
        'palmpay_reference',
        'palmpay_status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'bvn',
        'nin',
        'identity_type',
        'kyc_source',
        'kyc_upgraded',
        'kyc_upgraded_at',
        'director_bvn',
        'status',
        'is_test',
        'is_master',
        'activated_at',
        'expires_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'kyc_upgraded_at' => 'datetime',
        'amount' => 'decimal:2',
        'kyc_upgraded' => 'boolean',
        'is_master' => 'boolean',
        'is_test' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($va) {
            $va->uuid = $va->uuid ?? 'PWV_VA_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
            $va->account_id = $va->account_id ?? 'va_' . Str::random(12);
        });
    }

    /**
     * Get the company that owns this virtual account
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the company user (End User)
     */
    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class);
    }

    /**
     * Get transactions for this virtual account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}