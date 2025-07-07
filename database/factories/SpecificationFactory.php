<?php

namespace Database\Factories;

use App\Models\Specification;
use App\Models\Equipment;
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
        ];
    }
}
