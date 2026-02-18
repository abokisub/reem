<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Support\Str;

class CustomerIdGenerator
{
    /**
     * Generate a unique Customer ID.
     * Format: CUST-[TIMESTAMP]-[RANDOM]
     * Example: CUST-1715000000-AB12
     * 
     * @param string $prefix
     * @return string
     */
    public static function generate(string $prefix = 'CUST'): string
    {
        do {
            $timestamp = now()->format('ymdHis'); // Compact timestamp
            $random = strtoupper(Str::random(4));
            $customerId = "{$prefix}-{$timestamp}-{$random}";
        } while (self::exists($customerId));

        return $customerId;
    }

    /**
     * Check if ID exists in relevant tables.
     * 
     * @param string $id
     * @return bool
     */
    protected static function exists(string $id): bool
    {
        return User::where('customer_id', $id)->exists() ||
            CompanyUser::where('external_customer_id', $id)->exists();
    }
}
