<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->customer_id)) {
                $user->customer_id = \App\Services\CustomerIdGenerator::generate();
            }
        });
    }
    protected $table = 'users'; // Fixed from 'user' to match database
    public $timestamps = true; // Enabled timestamps as the table has them
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active_company_id',
        'name',
        'email',
        'password',
        'username',
        'phone',
        'balance',
        'type',
        'api_key',
        'kyc',
        'referral_balance',
        'ref',
        'status',
        'dob',
        'bvn',
        'nin',
        'next_of_kin',
        'occupation',
        'marital_status',
        'religion',
        'address',
        'city',
        'state',
        'postal_code',
        'id_card_path',
        'utility_bill_path',
        'customer_id',
        'kyc_status',
        'kyc_submitted_at',
        'app_key',
        'habukhan_key',
        'kyc_documents',
        'palmpay_bank_name',
        'palmpay_account_number',
        'palmpay_account_name',
        'palmpay_customer_id',
        'business_name',
        'rc_number',
        'description',
        'country',
        'lga',
        'website',
        'facebook',
        'x',
        'instagram',
        'linkedin',
        'onboarding_completed',
        'email_on_payment',
        'email_customer_on_success',
        'resend_failed_webhook',
        'resend_failed_webhook_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'apikey',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'next_of_kin' => 'array',
        'kyc_documents' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['balance'];

    /**
     * Get the company associated with the user
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * Check if user has a company
     */
    public function hasCompany(): bool
    {
        return $this->company()->exists();
    }

    /**
     * Get the user's balance.
     * For company accounts, returns the company wallet balance.
     * For individual accounts, returns the user's balance.
     *
     * @return float
     */
    public function getBalanceAttribute(): float
    {
        // Check if user has an active company
        if ($this->active_company_id) {
            $wallet = CompanyWallet::where('company_id', $this->active_company_id)
                ->where('currency', 'NGN')
                ->first();
            
            return $wallet ? (float) $wallet->balance : 0.00;
        }

        // For individual accounts, return user balance from database
        return (float) ($this->attributes['balance'] ?? 0.00);
    }
}