<?php

namespace App\Services;

use App\Models\CompanyFeeSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FeeService — Unified fee calculation with company-level override support.
 *
 * Priority: Company custom fee → System default
 *
 * Fee calculation formula:
 *   percentage_fee = amount * (percentage_rate / 100)
 *   total_fee      = percentage_fee + flat_fee
 *   if cap_amount  → total_fee = min(total_fee, cap_amount)
 *   if minimum_fee → total_fee = max(total_fee, minimum_fee)
 *   Round to 2 decimal places (kobo-safe)
 */
class FeeService
{
    // Maps internal transaction types to settings table column prefixes
    const TYPE_MAP = [
        'bank_transfer'      => 'transfer_charge',
        'settlement'         => 'payout_bank_charge',
        'external_transfer'  => 'payout_palmpay_charge',
    ];

    /**
     * Calculate fee for a transaction.
     *
     * @param int    $companyId
     * @param float  $amount         Amount in NGN (not kobo)
     * @param string $transactionType  One of: va_deposit, bank_transfer, settlement, external_transfer, kyc
     * @return array{fee: float, net: float, source: string, breakdown: array}
     */
    public function calculateFee(int $companyId, float $amount, string $transactionType): array
    {
        try {
            $config = $this->resolveConfig($companyId, $transactionType);
            $fee    = $this->compute($amount, $config);
            $net    = round($amount - $fee, 2);

            $result = [
                'fee'       => $fee,
                'net'       => $net,
                'source'    => $config['source'],
                'breakdown' => [
                    'amount'         => $amount,
                    'percentage_fee' => $config['percentage_fee'] ?? 0,
                    'flat_fee'       => $config['flat_fee'] ?? 0,
                    'cap_amount'     => $config['cap_amount'] ?? null,
                    'minimum_fee'    => $config['minimum_fee'] ?? null,
                    'fee_model'      => $config['fee_model'],
                ],
            ];

            Log::info('FeeService: fee calculated', [
                'company_id'       => $companyId,
                'transaction_type' => $transactionType,
                'amount'           => $amount,
                'fee'              => $fee,
                'source'           => $config['source'],
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('FeeService: calculation error', [
                'company_id' => $companyId,
                'amount'     => $amount,
                'type'       => $transactionType,
                'error'      => $e->getMessage(),
            ]);

            return ['fee' => 0, 'net' => $amount, 'source' => 'error_fallback', 'breakdown' => []];
        }
    }

    /**
     * Resolve fee config — company custom first, then system default.
     */
    public function resolveConfig(int $companyId, string $transactionType): array
    {
        // 1. Check company custom fee
        $custom = CompanyFeeSetting::where('company_id', $companyId)
            ->where('transaction_type', $transactionType)
            ->first();

        if ($custom) {
            return [
                'source'         => 'company_custom',
                'fee_model'      => $custom->fee_model,
                'percentage_fee' => (float) ($custom->percentage_fee ?? 0),
                'flat_fee'       => (float) ($custom->flat_fee ?? 0),
                'cap_amount'     => $custom->cap_amount !== null ? (float) $custom->cap_amount : null,
                'minimum_fee'    => $custom->minimum_fee !== null ? (float) $custom->minimum_fee : null,
            ];
        }

        // 2. Fall back to system default from settings table
        return $this->getSystemDefault($transactionType);
    }

    /**
     * Get system default fee config from settings table.
     */
    public function getSystemDefault(string $transactionType): array
    {
        // VA deposit comes from service_charges table (palmpay_va row)
        if ($transactionType === 'va_deposit') {
            $va = DB::table('service_charges')->where('service_name', 'palmpay_va')->first();
            if ($va) {
                $type = strtoupper($va->charge_type ?? 'PERCENT');
                $value = (float) ($va->charge_value ?? 0);
                $cap   = isset($va->charge_cap) && $va->charge_cap > 0 ? (float) $va->charge_cap : null;
                return [
                    'source'         => 'system_default',
                    'fee_model'      => ($type === 'FLAT') ? 'flat' : 'percentage',
                    'percentage_fee' => ($type !== 'FLAT') ? $value : 0,
                    'flat_fee'       => ($type === 'FLAT') ? $value : 0,
                    'cap_amount'     => $cap,
                    'minimum_fee'    => null,
                ];
            }
            return ['source' => 'system_default', 'fee_model' => 'percentage', 'percentage_fee' => 0.5, 'flat_fee' => 0, 'cap_amount' => null, 'minimum_fee' => null];
        }

        // KYC comes from service_charges table — map type to service_name
        if (str_starts_with($transactionType, 'kyc_')) {
            $serviceMap = [
                'kyc_basic_bvn'    => 'basic_bvn',
                'kyc_basic_nin'    => 'basic_nin',
                'kyc_enhanced_bvn' => 'enhanced_bvn',
                'kyc_enhanced_nin' => 'enhanced_nin',
                'kyc_liveness'     => 'liveness_detection',
                'kyc_face'         => 'face_comparison',
                'kyc_bank_verify'  => 'bank_account_verification',
                'kyc_credit_score' => 'credit_score',
                'kyc_blacklist'    => 'blacklist',
            ];
            $serviceName = $serviceMap[$transactionType] ?? null;
            if ($serviceName) {
                $row = DB::table('service_charges')->where('service_name', $serviceName)->first();
                if ($row) {
                    $type  = strtoupper($row->charge_type ?? 'FLAT');
                    $value = (float) ($row->charge_value ?? 0);
                    $cap   = isset($row->charge_cap) && $row->charge_cap > 0 ? (float) $row->charge_cap : null;
                    return [
                        'source'         => 'system_default',
                        'fee_model'      => ($type === 'FLAT') ? 'flat' : 'percentage',
                        'percentage_fee' => ($type !== 'FLAT') ? $value : 0,
                        'flat_fee'       => ($type === 'FLAT') ? $value : 0,
                        'cap_amount'     => $cap,
                        'minimum_fee'    => null,
                    ];
                }
            }
            return ['source' => 'system_default', 'fee_model' => 'flat', 'percentage_fee' => 0, 'flat_fee' => 0, 'cap_amount' => null, 'minimum_fee' => null];
        }

        $settings = DB::table('settings')->first();
        $prefix   = self::TYPE_MAP[$transactionType] ?? null;

        if ($settings && $prefix) {
            $type  = strtoupper($settings->{$prefix . '_type'} ?? 'FLAT');
            $value = (float) ($settings->{$prefix . '_value'} ?? 0);
            $cap   = isset($settings->{$prefix . '_cap'}) && $settings->{$prefix . '_cap'} > 0 ? (float) $settings->{$prefix . '_cap'} : null;

            if ($type === 'PERCENTAGE' || $type === 'PERCENT') {
                return [
                    'source'         => 'system_default',
                    'fee_model'      => 'percentage',
                    'percentage_fee' => $value,
                    'flat_fee'       => 0,
                    'cap_amount'     => $cap,
                    'minimum_fee'    => null,
                ];
            }

            return [
                'source'         => 'system_default',
                'fee_model'      => 'flat',
                'percentage_fee' => 0,
                'flat_fee'       => $value,
                'cap_amount'     => null,
                'minimum_fee'    => null,
            ];
        }

        // Hardcoded fallback
        return [
            'source'         => 'hardcoded_fallback',
            'fee_model'      => 'flat',
            'percentage_fee' => 0,
            'flat_fee'       => 0,
            'cap_amount'     => null,
            'minimum_fee'    => null,
        ];
    }

    /**
     * Core fee computation — safe, no floating point errors.
     */
    public function compute(float $amount, array $config): float
    {
        $percentageFee = 0;
        $flatFee       = (float) ($config['flat_fee'] ?? 0);
        $cap           = isset($config['cap_amount']) && $config['cap_amount'] > 0 ? (float) $config['cap_amount'] : null;
        $minimum       = isset($config['minimum_fee']) && $config['minimum_fee'] > 0 ? (float) $config['minimum_fee'] : null;

        // Calculate percentage portion
        if (!empty($config['percentage_fee']) && $config['percentage_fee'] > 0) {
            $rate          = min((float) $config['percentage_fee'], 100); // never > 100%
            $percentageFee = ($rate / 100) * $amount;
        }

        $totalFee = $percentageFee + $flatFee;

        // Apply cap
        if ($cap !== null) {
            $totalFee = min($totalFee, $cap);
        }

        // Apply minimum
        if ($minimum !== null) {
            $totalFee = max($totalFee, $minimum);
        }

        // Never negative
        $totalFee = max(0, $totalFee);

        return round($totalFee, 2);
    }

    /**
     * Upsert a company custom fee setting.
     */
    public function setCompanyFee(int $companyId, string $transactionType, array $data): CompanyFeeSetting
    {
        // Validate
        if (isset($data['percentage_fee']) && $data['percentage_fee'] > 100) {
            throw new \InvalidArgumentException('Percentage fee cannot exceed 100%');
        }
        if (isset($data['flat_fee']) && $data['flat_fee'] < 0) {
            throw new \InvalidArgumentException('Flat fee cannot be negative');
        }
        if (isset($data['cap_amount']) && isset($data['flat_fee']) && $data['cap_amount'] > 0 && $data['cap_amount'] < $data['flat_fee']) {
            throw new \InvalidArgumentException('Cap amount cannot be less than flat fee');
        }

        return CompanyFeeSetting::updateOrCreate(
            ['company_id' => $companyId, 'transaction_type' => $transactionType],
            [
                'fee_model'      => $data['fee_model'] ?? $this->inferModel($data),
                'percentage_fee' => $data['percentage_fee'] ?? 0,
                'flat_fee'       => $data['flat_fee'] ?? 0,
                'cap_amount'     => $data['cap_amount'] ?? null,
                'minimum_fee'    => $data['minimum_fee'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Remove company custom fee (revert to system default).
     */
    public function removeCompanyFee(int $companyId, string $transactionType): void
    {
        CompanyFeeSetting::where('company_id', $companyId)
            ->where('transaction_type', $transactionType)
            ->delete();
    }

    /**
     * Get all fee settings for a company (custom + defaults for missing types).
     */
    public function getCompanyFeeOverview(int $companyId): array
    {
        $result = [];

        foreach (array_keys(CompanyFeeSetting::TYPES) as $type) {
            $custom = CompanyFeeSetting::where('company_id', $companyId)
                ->where('transaction_type', $type)
                ->first();

            if ($custom) {
                $result[$type] = array_merge(['source' => 'custom'], $custom->toArray());
            } else {
                $default = $this->getSystemDefault($type);
                $result[$type] = array_merge(['source' => 'default'], $default);
            }
        }

        return $result;
    }

    private function inferModel(array $data): string
    {
        $hasPct  = !empty($data['percentage_fee']) && $data['percentage_fee'] > 0;
        $hasFlat = !empty($data['flat_fee']) && $data['flat_fee'] > 0;

        if ($hasPct && $hasFlat) return 'hybrid';
        if ($hasPct) return 'percentage';
        return 'flat';
    }
}
