<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'account_type',
        'company_id',
        'balance',
        'currency',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
