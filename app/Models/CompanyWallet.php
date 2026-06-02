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
        'restricted_balance',
        'ledger_balance',
        'pending_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'restricted_balance' => 'decimal:2',
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
     * Get available balance (total balance minus restricted/frozen amount)
     * This is the amount the company can actually withdraw.
     */
    public function availableBalance(): float
    {
        $available = (float) $this->balance - (float) $this->restricted_balance;
        return max(0, $available);
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
     * Checks against available balance (balance - restricted_balance)
     */
    public function debit(float $amount): void
    {
        if ($this->availableBalance() < $amount) {
            $restricted = (float) $this->restricted_balance;
            if ($restricted > 0) {
                throw new \Exception("Insufficient balance. ₦" . number_format($restricted, 2) . " is restricted due to ongoing investigation.");
            }
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
     * Remove from pending balance (never goes below zero)
     */
    public function removePending(float $amount): void
    {
        $current = (float) $this->pending_balance;
        if ($current <= 0) {
            return; // Nothing pending, skip
        }
        $deduct = min($amount, $current);
        $this->decrement('pending_balance', $deduct);
    }
}