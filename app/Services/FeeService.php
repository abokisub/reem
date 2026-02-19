<?php

namespace App\Services;

use App\Models\CompanyFeeSetting;
use Illuminate\Support\Facades\Log;

class FeeService
{
    /**
     * Calculates the fee for a transaction based on company settings and transaction type.
     */
    public function calculateFee(int $companyId, float $amount, string $transactionType = 'default'): array
    {
        // Try to find setting for specific transaction type
        $settings = CompanyFeeSetting::where('company_id', $companyId)
            ->where('transaction_type', $transactionType)
            ->first();

        // Fallback to 'default' type if specific one not found
        if (!$settings && $transactionType !== 'default') {
            $settings = CompanyFeeSetting::where('company_id', $companyId)
                ->where('transaction_type', 'default')
                ->first();
        }

        // Final fallback: Percentage if no settings found at all
        if (!$settings) {
            $fee = $amount * 0.015; // 1.5% default
            return [
                'fee' => round($fee, 2),
                'net' => round($amount - $fee, 2),
                'model' => 'system_default_percentage'
            ];
        }

        $fee = 0;
        switch ($settings->fee_model) {
            case 'flat':
                $fee = $settings->flat_fee;
                break;

            case 'percentage':
                $fee = $amount * ($settings->percentage_fee / 100);
                break;

            case 'hybrid':
                $percentagePart = $amount * ($settings->percentage_fee / 100);
                if ($settings->cap_amount && $settings->cap_amount > 0) {
                    $fee = min($percentagePart, $settings->cap_amount);
                } else {
                    $fee = $percentagePart;
                }
                break;
        }

        return [
            'fee' => round($fee, 2),
            'net' => round($amount - $fee, 2),
            'model' => $settings->fee_model
        ];
    }
}
