<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'last_name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'birth_date' => $this->faker->date(),
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'position' => $this->faker->jobTitle,
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
