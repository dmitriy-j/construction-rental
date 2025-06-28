<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'path' => $this->faker->imageUrl(800, 600, 'technics'),
            'is_main' => $this->faker->boolean(20), // 20% chance to be main
        ];
    }
}
