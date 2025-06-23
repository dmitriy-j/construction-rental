<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class CompanyFactory extends Factory
{
    protected $model = \App\Models\Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'password' => bcrypt('password'),
            'inn' => $this->faker->numerify('#############'),
            'ogrn' => $this->faker->numerify('###############'),
            'legal_address' => $this->faker->address,
            'bank_name' => $this->faker->company . ' Bank',
            'bank_account' => $this->faker->bankAccountNumber,
            'bik' => $this->faker->numerify('#########'),
            'correspondent_account' => $this->faker->bankAccountNumber,
            'director' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
        ];
    }
}
