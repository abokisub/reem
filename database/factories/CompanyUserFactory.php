<?php

namespace Database\Factories;

use App\Models\CompanyUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyUserFactory extends Factory
{
    protected $model = CompanyUser::class;

    public function definition()
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'company_id' => Company::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '080' . $this->faker->numerify('########'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
