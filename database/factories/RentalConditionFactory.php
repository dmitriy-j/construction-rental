<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use App\Models\RentalCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalConditionFactory extends Factory
{
    protected $model = RentalCondition::class;

    public function definition()
    {
        return [
            'shift_hours' => $this->faker->numberBetween(8, 12),
            'shifts_per_day' => $this->faker->numberBetween(1, 3),
            'transportation' => $this->faker->randomElement(['lessor', 'lessee', 'shared']),
            'fuel_responsibility' => $this->faker->randomElement(['lessor', 'lessee']),
            'extension_policy' => $this->faker->randomElement(['allowed', 'not_allowed', 'conditional']),
            'payment_type' => $this->faker->randomElement(['hourly', 'shift', 'daily', 'mileage', 'volume']),
            'fuel_consumption_rate' => $this->faker->randomFloat(2, 10, 50),
            'distance_rate' => $this->faker->randomFloat(2, 5, 20),
            'volume_rate' => $this->faker->randomFloat(2, 100, 500),
            'is_default' => false,
            'delivery_location_id' => Location::factory(),
            'delivery_cost_per_km' => $this->faker->randomFloat(2, 30, 100),
            'loading_cost' => $this->faker->randomFloat(2, 500, 2000),
            'unloading_cost' => $this->faker->randomFloat(2, 500, 2000),
            'company_id' => Company::factory(),
        ];
    }
}
