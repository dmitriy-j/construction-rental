<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = \App\Models\Company::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['lessor', 'lessee']),
            'legal_name' => $this->faker->company,
            'tax_system' => $this->faker->randomElement(['vat', 'no_vat']),
            'inn' => $this->faker->numerify('############'),
            'kpp' => $this->faker->numerify('#########'),
            'ogrn' => $this->faker->numerify('#############'),
            'okpo' => $this->faker->numerify('########'),
            'legal_address' => $this->faker->address,
            'actual_address' => $this->faker->address,
            'bank_name' => $this->faker->company . ' Bank',
            'bank_account' => $this->faker->numerify('####################'), // 20 символов
            'bik' => $this->faker->numerify('#########'),
            'correspondent_account' => $this->faker->numerify('####################'),
            'director_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'contacts' => $this->faker->name . ', ' . $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'status' => $this->faker->randomElement(['pending', 'verified', 'rejected']),
            'rejection_reason' => $this->faker->optional(0.3)->sentence,
            'verified_at' => $this->faker->optional(0.7)->dateTimeThisYear,
        ];
    }
}
