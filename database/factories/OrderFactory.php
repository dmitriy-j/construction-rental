<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Company;
use App\Models\User;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        $lessee = Company::where('type', 'lessee')->inRandomOrder()->first();
        $lessor = Company::where('type', 'lessor')->inRandomOrder()->first();
        $platform = Platform::first(); // Получаем главную платформу

        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = (clone $startDate)->modify('+'.rand(1,14).' days');

        return [
            'platform_id' => $platform->id, // Добавляем platform_id
            'lessee_company_id' => $lessee->id,
            'lessor_company_id' => $lessor->id,
            'user_id' => User::inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'active', 'completed']),
            'base_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'platform_fee' => $this->faker->randomFloat(2, 100, 5000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 1000),
            'total_amount' => $this->faker->randomFloat(2, 1500, 55000),
            'lessor_payout' => $this->faker->randomFloat(2, 1000, 50000), // Добавляем это поле
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            // Создаем 1-3 позиции для каждого заказа
            $itemsCount = rand(1, 3);

            for ($i = 0; $i < $itemsCount; $i++) {
                $order->items()->create([
                    'equipment_id' => \App\Models\Equipment::inRandomOrder()->first()->id,
                    'rental_term_id' => \App\Models\EquipmentRentalTerm::inRandomOrder()->first()->id,
                    'quantity' => rand(1, 3),
                    'base_price' => $this->faker->randomFloat(2, 500, 10000),
                    'price_per_unit' => $this->faker->randomFloat(2, 500, 5000),
                    'platform_fee' => $this->faker->randomFloat(2, 50, 500),
                    'discount_amount' => $this->faker->randomFloat(2, 0, 200),
                    'total_price' => $this->faker->randomFloat(2, 600, 12000),
                    'period_count' => rand(1, 14),
                ]);
            }
        });
    }
}
