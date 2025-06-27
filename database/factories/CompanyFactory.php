<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['lessor', 'lessee']),
            'legal_name' => $this->faker->company,
            'tax_system' => $this->faker->randomElement(['vat', 'no_vat']),
            'inn' => $this->faker->numerify('##########'), // 10 цифр
            'kpp' => $this->faker->numerify('#########'),  // 9 цифр
            'ogrn' => $this->faker->numerify('#############'), // 13 цифр
            'okpo' => $this->faker->numerify('##########'), // 10 цифр
            'legal_address' => $this->faker->address,
            'actual_address' => $this->faker->address,
            'bank_name' => $this->faker->company . ' Bank',
            'bank_account' => $this->faker->numerify('####################'), // 20 символов
            'bik' => $this->faker->numerify('#########'), // 9 цифр
            'correspondent_account' => $this->faker->numerify('####################'), // 20 символов
            'director_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'contacts' => $this->faker->name . ', ' . $this->faker->phoneNumber,
            'contact_email' => $this->faker->unique()->safeEmail, // Исправлено на contact_email
            'status' => $this->faker->randomElement(['pending', 'verified', 'rejected']),
            'rejection_reason' => $this->faker->optional(0.3)->sentence,
            'verified_at' => $this->faker->optional(0.7)->dateTimeThisYear,
        ];
    }
}
