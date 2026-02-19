<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'balance',
        'ledger_balance',
        'currency',
    ];

    public function credit(float $amount)
    {
        $this->balance += $amount;
        $this->ledger_balance += $amount;
        return $this->save();
    }

    public function debit(float $amount)
    {
        $this->balance -= $amount;
        $this->ledger_balance -= $amount;
        return $this->save();
    }
}
