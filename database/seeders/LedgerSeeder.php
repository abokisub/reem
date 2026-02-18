<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        $systemAccounts = [
            [
                'name' => 'PalmPay Clearing',
                'type' => 'bank_clearing',
                'uuid' => 'PWV_ACC_PALMPAY_CLEAR',
            ],
            [
                'name' => 'Gateway Revenue',
                'type' => 'revenue',
                'uuid' => 'PWV_ACC_GATEWAY_REV',
            ],
            [
                'name' => 'Settlement Clearing',
                'type' => 'settlement',
                'uuid' => 'PWV_ACC_SETTLE_CLEAR',
            ],
            [
                'name' => 'System Reserve',
                'type' => 'reserve',
                'uuid' => 'PWV_ACC_SYSTEM_RESERVE',
            ],
        ];

        foreach ($systemAccounts as $acc) {
            LedgerAccount::firstOrCreate(
                ['account_type' => $acc['type'], 'company_id' => null],
                [
                    'uuid' => $acc['uuid'],
                    'name' => $acc['name'],
                    'balance' => 0,
                    'currency' => 'NGN'
                ]
            );
        }
    }
}
