<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'description',
    ];

    /**
     * The "booted" method of the model.
     * Enforce append-only immutable ledger.
     */
    protected static function booted()
    {
        static::updating(function ($ledger) {
            \Illuminate\Support\Facades\Log::critical("🛡️ DANGER: Attempted to update append-only ledger ID: " . $ledger->id);
            return false;
        });

        static::deleting(function ($ledger) {
            \Illuminate\Support\Facades\Log::critical("🛡️ DANGER: Attempted to delete append-only ledger ID: " . $ledger->id);
            return false;
        });
    }

    public function debitAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'credit_account_id');
    }
}
