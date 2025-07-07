<?php

namespace Database\Factories;

use App\Models\CompletionAct;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompletionActFactory extends Factory
{
    protected $model = CompletionAct::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('-2 months', '-1 month');
        $endDate = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'order_id' => Order::factory(),
            'act_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'service_start_date' => $startDate,
            'service_end_date' => $endDate,
            'total_hours' => $this->faker->randomFloat(2, 10, 500),
            'total_downtime' => $this->faker->randomFloat(2, 0, 50),
            'penalty_amount' => $this->faker->randomFloat(2, 0, 10000),
            'total_amount' => $this->faker->randomFloat(2, 10000, 100000),
            'prepayment_amount' => $this->faker->randomFloat(2, 0, 50000),
            'final_amount' => $this->faker->randomFloat(2, 5000, 100000),
            'act_file_path' => $this->faker->optional()->url,
            'status' => $this->faker->randomElement([
                'draft', 'signed_lessor', 'signed_lessee', 'completed'
                ]),
        ];
    }
}