<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fee Service - Unified Fee Calculation
 * 
 * Handles all fee calculations across the platform
 */
class FeeService
{
    /**
     * Calculate fee for a transaction
     * 
     * @param int $companyId
     * @param float $amount
     * @param string $transactionType (va_deposit, transfer, withdrawal, etc.)
     * @return array ['fee' => float, 'net' => float, 'model' => string]
     */
    public function calculateFee(int $companyId, float $amount, string $transactionType): array
    {
        try {
            // Get company settings
            $company = DB::table('companies')->where('id', $companyId)->first();
            
            // Get global settings
            $settings = DB::table('settings')->first();
            
            // Determine which fee configuration to use
            $feeConfig = $this->getFeeConfig($company, $settings, $transactionType);
            
            // Calculate fee
            $fee = $this->applyFeeCalculation($amount, $feeConfig);
            
            // Net amount (what company receives after fee)
            $net = $amount - $fee;
            
            return [
                'fee' => round($fee, 2),
                'net' => round($net, 2),
                'model' => $feeConfig['model'],
                'type' => $feeConfig['type'],
                'value' => $feeConfig['value'],
                'cap' => $feeConfig['cap'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('FeeService: Fee calculation failed', [
                'company_id' => $companyId,
                'amount' => $amount,
                'type' => $transactionType,
                'error' => $e->getMessage()
            ]);
            
            // Return zero fee on error (fail-safe)
            return [
                'fee' => 0,
                'net' => $amount,
                'model' => 'error_fallback'
            ];
        }
    }
    
    /**
     * Get fee configuration for transaction type
     */
    private function getFeeConfig($company, $settings, string $transactionType): array
    {
        // Map transaction types to settings columns
        $typeMap = [
            'va_deposit' => 'virtual_funding',
            'transfer' => 'transfer_charge',
            'withdrawal' => 'withdrawal_charge',
            'payout' => 'payout_charge'
        ];
        
        $settingsKey = $typeMap[$transactionType] ?? 'virtual_funding';
        
        // Check if company has custom fee settings
        if ($company && isset($company->custom_fees_enabled) && $company->custom_fees_enabled) {
            $customFeeColumn = 'custom_' . $settingsKey;
            $customTypeColumn = $customFeeColumn . '_type';
            $customValueColumn = $customFeeColumn . '_value';
            $customCapColumn = $customFeeColumn . '_cap';
            
            if (isset($company->$customValueColumn)) {
                return [
                    'model' => 'company_custom',
                    'type' => $company->$customTypeColumn ?? 'PERCENT',
                    'value' => (float) $company->$customValueColumn,
                    'cap' => isset($company->$customCapColumn) ? (float) $company->$customCapColumn : null
                ];
            }
        }
        
        // Use system default settings
        if ($settings) {
            $typeColumn = $settingsKey . '_type';
            $valueColumn = $settingsKey . '_value';
            $capColumn = $settingsKey . '_cap';
            
            return [
                'model' => 'system_default_' . ($settings->$typeColumn ?? 'percentage'),
                'type' => $settings->$typeColumn ?? 'PERCENT',
                'value' => (float) ($settings->$valueColumn ?? 0.5),
                'cap' => isset($settings->$capColumn) ? (float) $settings->$capColumn : 500
            ];
        }
        
        // Fallback to hardcoded defaults
        return [
            'model' => 'hardcoded_fallback',
            'type' => 'PERCENT',
            'value' => 0.5,
            'cap' => 500
        ];
    }
    
    /**
     * Apply fee calculation based on type
     */
    private function applyFeeCalculation(float $amount, array $config): float
    {
        $type = strtoupper($config['type']);
        $value = $config['value'];
        $cap = $config['cap'] ?? null;
        
        if ($type === 'FLAT') {
            return $value;
        }
        
        if ($type === 'PERCENT' || $type === 'PERCENTAGE') {
            $fee = ($value / 100) * $amount;
            
            // Apply cap if specified
            if ($cap !== null && $fee > $cap) {
                return $cap;
            }
            
            return $fee;
        }
        
        // Unknown type, return zero
        return 0;
    }
}
