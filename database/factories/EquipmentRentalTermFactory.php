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
        $equipment = Equipment::inRandomOrder()->first() ?? Equipment::factory()->create();

        return [
            'equipment_id' => $equipment->id,
            'price_per_hour' => $this->faker->numberBetween(500, 5000),
            'price_per_km' => $this->faker->optional(0.7)->numberBetween(10, 100), // 70% chance not null
            'min_rental_hours' => $this->faker->numberBetween(1, 24),
            'currency' => 'RUB',
            'delivery_days' => $this->faker->numberBetween(1, 3),
            'return_policy' => $this->faker->sentence
        ];
    }
}
