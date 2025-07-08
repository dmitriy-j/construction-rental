<?php

namespace Database\Factories;

use App\Models\Waybill;
use App\Models\Order;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaybillFactory extends Factory
{
    protected $model = Waybill::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'equipment_id' => Equipment::factory(),
            'operator_id' => User::factory(),
            'work_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'hours_worked' => $this->faker->randomFloat(2, 1, 24),
            'downtime_hours' => $this->faker->randomFloat(2, 0, 8),
            'downtime_cause' => $this->faker->optional(0.5)->randomElement([
            'lessee', 'lessor', 'force_majeure'
            ]),
            'operator_signature_path' => $this->faker->optional()->imageUrl(),
            'customer_signature_path' => $this->faker->optional()->imageUrl(),
            'notes' => $this->faker->optional()->sentence,
        ];
    }
}
