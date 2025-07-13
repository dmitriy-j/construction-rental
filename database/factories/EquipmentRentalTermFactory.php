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

        $periods = ['час', 'смена', 'сутки', 'месяц'];
        $existingPeriods = $equipment->rentalTerms()->pluck('period')->toArray();
        $availablePeriods = array_diff($periods, $existingPeriods);

        // Если все периоды уже использованы, используем случайный
        $period = !empty($availablePeriods)
            ? $this->faker->randomElement($availablePeriods)
            : $this->faker->randomElement($periods);

        return [
            'equipment_id' => $equipment->id,
            'period' => $period,
            'price' => $this->faker->numberBetween(1000, 50000),
            'currency' => 'RUB',
            'delivery_price' => $this->faker->numberBetween(500, 5000),
            'delivery_days' => $this->faker->numberBetween(1, 3),
            'return_policy' => $this->faker->sentence
        ];
    }
}
