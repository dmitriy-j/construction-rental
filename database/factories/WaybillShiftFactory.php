<?php

namespace Database\Factories;

use App\Models\Waybill;
use App\Models\WaybillShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaybillShiftFactory extends Factory
{
    protected $model = WaybillShift::class;

    public function definition()
    {
        return [
            'waybill_id' => Waybill::factory(),
            'shift_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'shift_type' => $this->faker->randomElement(['day', 'night']),
            'operator_id' => \App\Models\Operator::factory(),
            'hours_worked' => $this->faker->randomFloat(2, 1, 24),
            'downtime_hours' => $this->faker->randomFloat(2, 0, 8),
            'downtime_cause' => $this->faker->optional()->randomElement([
                'lessee', 'lessor', 'force_majeure',
            ]),
            'odometer_start' => $this->faker->numberBetween(1000, 10000),
            'odometer_end' => $this->faker->numberBetween(1001, 20000),
            'fuel_start' => $this->faker->randomFloat(2, 20, 100),
            'fuel_end' => $this->faker->randomFloat(2, 5, 50),
            'fuel_refilled_liters' => $this->faker->randomFloat(2, 0, 50),
            'hourly_rate' => $this->faker->randomFloat(2, 500, 2000),
            'work_description' => $this->faker->sentence,
        ];
    }
}
