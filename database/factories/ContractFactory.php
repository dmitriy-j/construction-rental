<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'order_id' => Order::factory(),
            'number' => 'CT-'.$this->faker->unique()->randomNumber(6),
            'start_date' => $this->faker->dateTimeBetween('-1 year'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'description' => $this->faker->sentence,
            'payment_type' => $this->faker->randomElement(['prepay', 'postpay', 'mixed']),
            'documentation_deadline' => $this->faker->numberBetween(1, 30),
            'payment_deadline' => $this->faker->numberBetween(1, 30),
            'penalty_rate' => $this->faker->randomFloat(2, 0.1, 1),
            'file_path' => $this->faker->url,
        ];
    }
}
