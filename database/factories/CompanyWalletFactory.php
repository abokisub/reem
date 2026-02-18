<?php

namespace Database\Factories;

use App\Models\CompanyWallet;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyWalletFactory extends Factory
{
    protected $model = CompanyWallet::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'currency' => 'NGN',
            'balance' => 0,
            'ledger_balance' => 0,
            'pending_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
