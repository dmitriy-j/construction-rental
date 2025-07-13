<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_base_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'total_platform_fee' => $this->faker->randomFloat(2, 100, 10000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 5000),
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ];
    }
}
