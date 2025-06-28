<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalTermFactory extends Factory
{
    public function definition(): array
    {
        $periods = ['час', 'смена', 'сутки', 'месяц'];

        return [
            'equipment_id' => Equipment::factory(),
            'period' => $this->faker->randomElement($periods),
            'price' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'RUB',
        ];
    }
}
