<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementQueue extends Model
{
    protected $table = 'settlement_queue';

    protected $fillable = [
        'company_id',
        'transaction_id',
        'amount',
        'status',
        'transaction_date',
        'scheduled_settlement_date',
        'actual_settlement_date',
        'settlement_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'scheduled_settlement_date' => 'datetime',
        'actual_settlement_date' => 'datetime',
    ];

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Check if settlement is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if settlement is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
