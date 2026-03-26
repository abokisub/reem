<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'transaction_type',
        'fee_model',
        'flat_fee',
        'percentage_fee',
        'cap_amount',
        'minimum_fee',
        'notes',
    ];

    protected $casts = [
        'flat_fee'       => 'decimal:2',
        'percentage_fee' => 'decimal:4',
        'cap_amount'     => 'decimal:2',
        'minimum_fee'    => 'decimal:2',
    ];

    // Supported transaction types
    const TYPES = [
        'va_deposit'         => 'Virtual Account Deposit',
        'settlement'         => 'Settlement Withdrawal',
        'bank_transfer'      => 'Pay With Bank Transfer',
        'external_transfer'  => 'External Transfer (Other Banks)',
        'kyc_basic_bvn'      => 'KYC — Basic BVN',
        'kyc_basic_nin'      => 'KYC — Basic NIN',
        'kyc_enhanced_bvn'   => 'KYC — Enhanced BVN',
        'kyc_enhanced_nin'   => 'KYC — Enhanced NIN',
        'kyc_liveness'       => 'KYC — Liveness Detection',
        'kyc_face'           => 'KYC — Face Comparison',
        'kyc_bank_verify'    => 'KYC — Bank Account Verification',
        'kyc_credit_score'   => 'KYC — Credit Score',
        'kyc_blacklist'      => 'KYC — Blacklist Check',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
