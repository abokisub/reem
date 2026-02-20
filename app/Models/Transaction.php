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
        'provider_fee',
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
        'is_refunded',
        'refund_transaction_id',
        'provider',
        'reconciliation_status',
        'reconciled_at',
        // New fields from transaction normalization
        'session_id',
        'transaction_ref',
        'transaction_type',
        'settlement_status',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            // Legacy fields - keep existing logic
            $transaction->transaction_id = $transaction->transaction_id ?? self::generateTransactionId();
            $transaction->reference = $transaction->reference ?? self::generateReference();
            
            // Transaction normalization fields - use TransactionValidator
            $validator = new \App\Validators\TransactionValidator();
            
            // Auto-generate session_id if not provided
            if (!$transaction->session_id) {
                $transaction->session_id = 'sess_' . \Illuminate\Support\Str::uuid();
            }
            
            // Auto-generate transaction_ref if not provided
            if (!$transaction->transaction_ref) {
                $transaction->transaction_ref = self::generateTransactionRef();
            }
            
            // Calculate net_amount automatically based on transaction type
            // For CREDIT (deposits): net_amount = amount - fee (what company receives after fee)
            // For DEBIT (withdrawals/transfers): net_amount = amount (what recipient receives, fee is separate)
            if (!isset($transaction->net_amount)) {
                if ($transaction->type === 'credit') {
                    // Deposit: Company receives amount minus fee
                    $transaction->net_amount = $transaction->amount - ($transaction->fee ?? 0);
                } else {
                    // Withdrawal/Transfer: Recipient receives the full amount, fee is added to total
                    $transaction->net_amount = $transaction->amount;
                }
            }
            
            // Set default settlement_status based on type and status
            if (!$transaction->settlement_status && $transaction->transaction_type && $transaction->status) {
                $transaction->settlement_status = self::determineSettlementStatus(
                    $transaction->transaction_type,
                    $transaction->status
                );
            }
        });
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'provider_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'reconciled_at' => 'datetime',
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
     * Get the customer (alias for companyUser)
     * Transaction belongs to CompanyUser as customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class, 'company_user_id');
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
     * Generate unique transaction reference (TXN + 12 uppercase alphanumeric)
     */
    private static function generateTransactionRef(): string
    {
        return 'TXN' . strtoupper(\Illuminate\Support\Str::random(12));
    }

    /**
     * Determine settlement status based on transaction type and status
     * 
     * @param string $type Transaction type
     * @param string $status Transaction status
     * @return string Settlement status
     */
    private static function determineSettlementStatus(string $type, string $status): string
    {
        // Internal accounting entries don't require settlement
        if (in_array($type, ['fee_charge', 'kyc_charge', 'manual_adjustment'])) {
            return 'not_applicable';
        }
        
        // Failed/reversed transactions don't settle
        if (in_array($status, ['failed', 'reversed'])) {
            return 'not_applicable';
        }
        
        // Successful transactions are settled
        if ($status === 'successful') {
            return 'settled';
        }
        
        // Default to unsettled for pending/processing
        return 'unsettled';
    }

    /**
     * Scope: Filter to customer-facing transaction types
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomerFacing($query)
    {
        return $query->whereIn('transaction_type', [
            'va_deposit',
            'api_transfer',
            'company_withdrawal',
            'refund'
        ]);
    }

    /**
     * Scope: Filter to internal transaction types
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInternal($query)
    {
        return $query->whereIn('transaction_type', [
            'fee_charge',
            'kyc_charge',
            'manual_adjustment'
        ]);
    }

    /**
     * Scope: Filter to settled transactions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSettled($query)
    {
        return $query->where('settlement_status', 'settled');
    }

    /**
     * Scope: Filter to unsettled transactions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnsettled($query)
    {
        return $query->where('settlement_status', 'unsettled');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['successful', 'success', 'settled']);
    }

    /**
     * Check if transaction is in a pending/processing state
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'initiated', 'debited', 'processing']);
    }

    /**
     * Check if transaction has been reversed
     */
    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Check if transaction has been settled
     */
    public function isSettled(): bool
    {
        return $this->status === 'settled';
    }

    /**
     * Check if this transaction needs reconciliation
     */
    public function needsReconciliation(): bool
    {
        return $this->status === 'processing' && $this->reconciliation_status === 'pending';
    }
}