<?php

namespace Database\Factories;

use App\Models\Equipment; // Добавьте этот импорт
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EquipmentRentalTerm>
 */
class EquipmentRentalTermFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(), // Теперь Equipment будет найден
            'period' => $this->faker->randomElement(['час', 'смена', 'сутки', 'месяц']),
            'price' => $this->faker->numberBetween(1000, 50000),
            'currency' => 'RUB',
            'delivery_price' => $this->faker->numberBetween(500, 5000),
            'delivery_days' => $this->faker->numberBetween(1, 3),
            'return_policy' => $this->faker->sentence
        ];
    }
}
