<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'fee_model',
        'flat_fee',
        'percentage_fee',
        'cap_amount',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
