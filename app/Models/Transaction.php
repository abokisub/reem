<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'company_id',
        'company_user_id',
        'user_id',
        'type',
        'category',
        'amount',
        'fee',
        'net_amount',
        'total_amount',
        'currency',
        'status',
        'reference',
        'palmpay_reference',
        'provider_reference',
        'external_reference',
        'virtual_account_id',
        'recipient_account_number',
        'recipient_account_name',
        'recipient_bank_code',
        'recipient_bank_name',
        'description',
        'metadata',
        'channel',
        'error_message',
        'balance_before',
        'balance_after',
        'processed_at',
        'is_test',
        'processed_at',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->transaction_id = $transaction->transaction_id ?? self::generateTransactionId();
            $transaction->reference = $transaction->reference ?? self::generateReference();
        });
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the company
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
     * Get the user (Legacy)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the virtual account
     */
    public function virtualAccount(): BelongsTo
    {
        return $this->belongsTo(VirtualAccount::class);
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'txn_' . uniqid() . rand(10000, 99999);
    }

    /**
     * Generate unique reference
     */
    public static function generateReference(): string
    {
        return 'REF' . strtoupper(uniqid()) . rand(1000, 9999);
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'processing';
    }
}