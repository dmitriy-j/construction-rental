<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DiscountTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountTierFactory extends Factory
{
    protected $model = DiscountTier::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(),
            'min_turnover' => $this->faker->randomFloat(2, 0, 1000000),
            'discount_percent' => $this->faker->randomFloat(2, 1, 20),
        ];
    }
}
