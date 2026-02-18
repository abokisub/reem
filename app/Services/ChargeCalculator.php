<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ChargeCalculator
{
    /**
     * Calculate charge based on type (FLAT or PERCENT)
     *
     * @param string $type Charge type: 'FLAT' or 'PERCENT'
     * @param float $value Charge value (flat amount or percentage)
     * @param float $amount Transaction amount
     * @param float|null $cap Maximum charge for percentage type
     * @return float Calculated charge
     */
    public static function calculate(string $type, float $value, float $amount, ?float $cap = null): float
    {
        if ($type === 'FLAT') {
            return $value;
        }

        if ($type === 'PERCENT') {
            $charge = ($value / 100) * $amount;

            // Apply cap if specified
            if ($cap !== null && $charge > $cap) {
                return $cap;
            }

            return round($charge, 2);
        }

        return 0;
    }

    /**
     * Get service charge configuration and calculate charge
     *
     * @param string $serviceCategory Service category (kyc, payment, payout, vending)
     * @param string $serviceName Service name (enhanced_bvn, palmpay_va, etc.)
     * @param float $amount Transaction amount (default 0 for flat charges)
     * @return array Charge details including calculated charge
     */
    public static function getServiceCharge(string $serviceCategory, string $serviceName, float $amount = 0): array
    {
        $charge = DB::table('service_charges')
            ->where('service_category', $serviceCategory)
            ->where('service_name', $serviceName)
            ->where('is_active', true)
            ->first();

        if (!$charge) {
            return [
                'charge' => 0,
                'type' => 'FLAT',
                'value' => 0,
                'cap' => null,
                'display_name' => null
            ];
        }

        $calculatedCharge = self::calculate(
            $charge->charge_type,
            (float) $charge->charge_value,
            $amount,
            $charge->charge_cap ? (float) $charge->charge_cap : null
        );

        return [
            'charge' => $calculatedCharge,
            'type' => $charge->charge_type,
            'value' => (float) $charge->charge_value,
            'cap' => $charge->charge_cap ? (float) $charge->charge_cap : null,
            'display_name' => $charge->display_name
        ];
    }

    /**
     * Get all service charges for a category
     *
     * @param string $serviceCategory Service category
     * @return array Array of service charges
     */
    public static function getCategoryCharges(string $serviceCategory): array
    {
        $charges = DB::table('service_charges')
            ->where('service_category', $serviceCategory)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $result = [];

        foreach ($charges as $charge) {
            $result[$charge->service_name] = [
                'name' => $charge->display_name,
                'type' => $charge->charge_type,
                'value' => (float) $charge->charge_value,
                'cap' => $charge->charge_cap ? (float) $charge->charge_cap : null
            ];
        }

        return $result;
    }

    /**
     * Calculate payment/payout charge from settings table
     *
     * @param string $chargePrefix Column prefix in settings table (transfer_charge, wallet_charge, etc.)
     * @param float $amount Transaction amount
     * @return array Charge details
     */
    public static function getSettingsCharge(string $chargePrefix, float $amount = 0): array
    {
        $settings = DB::table('settings')->first();

        if (!$settings) {
            return [
                'charge' => 0,
                'type' => 'FLAT',
                'value' => 0,
                'cap' => null
            ];
        }

        $typeColumn = $chargePrefix . '_type';
        $valueColumn = $chargePrefix . '_value';
        $capColumn = $chargePrefix . '_cap';

        $type = $settings->$typeColumn ?? 'FLAT';
        $value = (float) ($settings->$valueColumn ?? 0);
        $cap = isset($settings->$capColumn) ? (float) $settings->$capColumn : null;

        $calculatedCharge = self::calculate($type, $value, $amount, $cap);

        return [
            'charge' => $calculatedCharge,
            'type' => $type,
            'value' => $value,
            'cap' => $cap
        ];
    }
}
