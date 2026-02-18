<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'currency',
        'balance',
        'ledger_balance',
        'pending_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'ledger_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
    ];

    /**
     * Get the company that owns this wallet
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Credit the wallet
     */
    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('ledger_balance', $amount);
    }

    /**
     * Debit the wallet
     */
    public function debit(float $amount): void
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $this->decrement('balance', $amount);
        $this->decrement('ledger_balance', $amount);
    }

    /**
     * Add to pending balance
     */
    public function addPending(float $amount): void
    {
        $this->increment('pending_balance', $amount);
    }

    /**
     * Remove from pending balance
     */
    public function removePending(float $amount): void
    {
        $this->decrement('pending_balance', $amount);
    }
}