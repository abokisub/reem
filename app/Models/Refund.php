<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Refund Model
 * 
 * Represents a refund transaction for PalmPay pay-in orders
 */
class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'refund_id',
        'transaction_id',
        'palmpay_refund_no',
        'palmpay_order_no',
        'amount',
        'currency',
        'reason',
        'refund_type',
        'initiated_by',
        'admin_notes',
        'status',
        'error_message',
        'metadata',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the company that owns the refund
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the transaction associated with this refund
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Get the user who initiated the refund (for manual refunds)
     */
    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Scope for pending refunds
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed refunds
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed refunds
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
