<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'number' => 'CT-' . $this->faker->unique()->randomNumber(6),
            'description' => $this->faker->sentence,
            'payment_type' => $this->faker->randomElement(['prepay', 'postpay', 'mixed']),
            'documentation_deadline' => $this->faker->numberBetween(1, 30), // дни
        'payment_deadline' => $this->faker->numberBetween(1, 30),       // дни
            'penalty_rate' => $this->faker->randomFloat(2, 0.1, 1),
            'file_path' => $this->faker->url,
        ];
    }
}