<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Specification;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecificationFactory extends Factory
{
    protected $model = Specification::class;

    public function definition()
    {
        return [
            'equipment_id' => Equipment::factory(),
            'key' => $this->faker->word,
            'value' => $this->faker->words(3, true),
            'weight' => $this->faker->numberBetween(1000, 35000),
            'length' => $this->faker->randomFloat(2, 1, 20),
            'width' => $this->faker->randomFloat(2, 1, 5),
            'height' => $this->faker->randomFloat(2, 1, 5),
        ];
    }
}
