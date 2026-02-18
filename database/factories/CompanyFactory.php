<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'uuid' => Str::random(40),
            'business_id' => Str::random(40),
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => 'active',  // Default to active for testing
            'is_active' => true,   // Default to active for testing
            'transaction_fee_percentage' => 1.5,
            'api_key' => 'pk_live_' . Str::random(32),
            'api_secret_key' => 'sk_live_' . Str::random(32),
            'test_api_key' => 'pk_test_' . Str::random(32),
            'test_secret_key' => 'sk_test_' . Str::random(32),
            'webhook_secret' => 'whsec_' . Str::random(32),
            'test_webhook_secret' => 'whsec_test_' . Str::random(32),
        ];
    }
}
