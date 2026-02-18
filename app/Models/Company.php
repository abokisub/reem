<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'uuid',
        'api_public_key',
        'api_secret_key',
        'settlement_bank_name',
        'settlement_account_number',
        'settlement_account_name',
        'user_id',
        'business_id',
        'name',
        'business_type',
        'business_category',
        'email',
        'phone',
        'address',
        'public_key',
        'secret_key',
        'api_key',
        'webhook_url',
        'webhook_secret',
        'webhook_enabled',
        'test_public_key',
        'test_secret_key',
        'test_api_key',
        'test_webhook_url',
        'test_webhook_secret',
        'status',
        'transaction_fee_percentage',
        'minimum_balance',
        'daily_limit',
        'monthly_limit',
        'single_transaction_limit',
        'kyc_status',
        'kyc_rejection_reason',
        'kyc_reviewed_at',
        'kyc_reviewed_by',
        'business_registration_number',
        'bvn',
        'nin',
        'director_bvn',
        'director_nin',
        'bank_name',
        'account_number',
        'account_name',
        'bank_code',
        'palmpay_account_number',
        'palmpay_account_name',
        'palmpay_bank_name',
        'palmpay_bank_code',
        'directors',
        'shareholders',
        'kyc_documents',
        'verification_data',
        'identity_details',
        'is_active',
    ];

    protected $casts = [
        'webhook_secret' => 'encrypted',
        'test_webhook_secret' => 'encrypted',
        'old_webhook_secret' => 'encrypted',
        'old_test_webhook_secret' => 'encrypted',
        'webhook_secret_expires_at' => 'datetime',
        'test_webhook_secret_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'webhook_enabled' => 'boolean',
        'transaction_fee_percentage' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'single_transaction_limit' => 'decimal:2',
        'kyc_documents' => 'array',
        'verification_data' => 'array',
        'identity_details' => 'array',
        'directors' => 'array',
        'shareholders' => 'array',
    ];

    protected $hidden = [
        'secret_key',
        'api_secret_key',
        'test_secret_key',
        'webhook_secret',
        'old_webhook_secret',
        'test_webhook_secret',
        'old_test_webhook_secret',
    ];

    /**
     * Get the user that owns the company
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company's wallet
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(CompanyWallet::class)->where('currency', 'NGN');
    }

    /**
     * Get all wallets (multi-currency support)
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(CompanyWallet::class);
    }

    /**
     * Get virtual accounts
     */
    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }

    /**
     * Get transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if company is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active;
    }

    /**
     * Rotate Webhook Secret with a 24-hour overlap window.
     */
    public function rotateWebhookSecret(bool $isTest = false): string
    {
        $newSecret = 'whsec_' . bin2hex(random_bytes(32));

        if ($isTest) {
            $this->old_test_webhook_secret = $this->test_webhook_secret;
            $this->setAttribute('test_webhook_secret', $newSecret);
            $this->test_webhook_secret_expires_at = now()->addHours(24);
        } else {
            $this->old_webhook_secret = $this->webhook_secret;
            $this->setAttribute('webhook_secret', $newSecret);
            $this->webhook_secret_expires_at = now()->addHours(24);
        }

        $this->save();
        return $newSecret;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($company) {
            $keys = self::generateApiKeys();
            $testKeys = self::generateApiKeys('test_');

            $company->uuid = $company->uuid ?? bin2hex(random_bytes(20)); // 40 chars hex
            $company->business_id = $company->business_id ?? bin2hex(random_bytes(20)); // 40 chars hex
            $company->is_active = $company->is_active ?? false; // Locked by default (only if not set)
            $company->status = $company->status ?? 'pending'; // Initial status (only if not set)

            $company->api_public_key = $company->api_public_key ?? $keys['api_public_key'];
            $company->api_secret_key = $company->api_secret_key ?? $keys['api_secret_key'];

            $company->test_public_key = $company->test_public_key ?? $testKeys['api_public_key'];
            $company->test_secret_key = $company->test_secret_key ?? $testKeys['api_secret_key'];
            $company->test_api_key = $company->test_api_key ?? $company->test_public_key;

            // Backwards compatibility for now
            $company->public_key = $company->api_public_key;
            $company->secret_key = $company->api_secret_key;
            $company->api_key = $company->api_public_key;
        });
    }

    /**
     * Generate API keys
     */
    public static function generateApiKeys(string $prefix = ''): array
    {
        return [
            'api_public_key' => $prefix . bin2hex(random_bytes(20)), // 40 chars hex
            'api_secret_key' => $prefix . bin2hex(random_bytes(60)), // 120 chars hex
        ];
    }
}