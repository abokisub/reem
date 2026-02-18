<?php

namespace Database\Factories;

use App\Models\VirtualAccount;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VirtualAccountFactory extends Factory
{
    protected $model = VirtualAccount::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'company_user_id' => CompanyUser::factory(),
            'account_number' => '10' . $this->faker->numerify('########'),
            'account_name' => $this->faker->name(),
            'bank_name' => 'PalmPay',
            'bank_code' => '100033',
            'account_type' => 'static',
            'palmpay_reference' => 'PP_' . Str::random(20),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
