<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndUserTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'virtual_account_id',
        'transaction_reference',
        'customer_reference',
        'customer_name',
        'customer_email',
        'amount',
        'fee',
        'net_amount',
        'status',
        'payment_method',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this transaction.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the virtual account for this transaction.
     */
    public function virtualAccount()
    {
        return $this->belongsTo(VirtualAccount::class);
    }

    /**
     * Scope for successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for transactions within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for company transactions.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Mark transaction as successful.
     */
    public function markAsSuccessful()
    {
        $this->update([
            'status' => 'successful',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed',
        ]);
    }

    /**
     * Calculate net amount (amount - fee).
     */
    public static function calculateNetAmount($amount, $fee = 0)
    {
        return $amount - $fee;
    }
}
