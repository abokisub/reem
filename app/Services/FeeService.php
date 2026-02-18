<?php

namespace App\Services;

use App\Models\CompanyFeeSetting;
use Illuminate\Support\Facades\Log;

class FeeService
{
    /**
     * Calculates the fee for a transaction based on company settings.
     */
    public function calculateFee(int $companyId, float $amount): array
    {
        $settings = CompanyFeeSetting::where('company_id', $companyId)->first();

        // Default: 1.5% if no settings found
        if (!$settings) {
            $fee = $amount * 0.015;
            return [
                'fee' => $fee,
                'net' => $amount - $fee,
                'model' => 'default_percentage'
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
                // Apply cap if specified
                if ($settings->cap_amount && $settings->cap_amount > 0) {
                    $fee = min($percentagePart, $settings->cap_amount);
                } else {
                    $fee = $percentagePart;
                }
                // Add flat fee if part of hybrid? Usually it's either/or or percentage with cap.
                // Keeping it percentage with cap for now as per "1.5% capped at 2k" example.
                break;
        }

        return [
            'fee' => round($fee, 2),
            'net' => round($amount - $fee, 2),
            'model' => $settings->fee_model
        ];
    }
}
