<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Company;
use App\Models\User;
use App\Models\Platform;
use App\Models\Equipment; // Добавляем импорт
use App\Models\EquipmentRentalTerm; // Добавляем импорт
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        $lessee = Company::where('is_lessee', true)->inRandomOrder()->first();
        $lessor = Company::where('is_lessor', true)->inRandomOrder()->first();
        $platform = Platform::first();

        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = (clone $startDate)->modify('+'.rand(1,14).' days');

        return [
            'platform_id' => $platform->id,
            'lessee_company_id' => $lessee->id,
            'lessor_company_id' => $lessor->id,
            'user_id' => User::inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'active', 'completed']),
            'base_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'platform_fee' => $this->faker->randomFloat(2, 100, 5000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 1000),
            'total_amount' => $this->faker->randomFloat(2, 1500, 55000),
            'lessor_payout' => $this->faker->randomFloat(2, 1000, 50000),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            $pricingService = new \App\Services\PricingService();

            $itemsCount = rand(1, 3);
            for ($i = 0; $i < $itemsCount; $i++) {
                $equipment = Equipment::with('category')->inRandomOrder()->first();
                $rentalTerm = EquipmentRentalTerm::inRandomOrder()->first();
                $periodCount = rand(1, 14);

                $priceData = $pricingService->calculatePrice(
                    $rentalTerm,
                    $order->lesseeCompany,
                    $periodCount
                );

                $quantity = rand(1, 3);

                $order->items()->create([
                    'equipment_id' => $equipment->id,
                    'rental_term_id' => $rentalTerm->id,
                    'quantity' => $quantity,
                    'base_price' => $priceData['base_price_per_unit'],
                    'price_per_unit' => $priceData['base_price_per_unit'] + $priceData['platform_fee_per_unit'],
                    'platform_fee' => $priceData['platform_fee'],
                    'discount_amount' => $priceData['discount_amount'],
                    'total_price' => $priceData['final_price'],
                    'period_count' => $periodCount,
                ]);
            }

            $order->update([
                'base_amount' => $order->items->sum(function($item) {
                    return $item->base_price * $item->quantity * $item->period_count;
                }),
                'platform_fee' => $order->items->sum('platform_fee'),
                'total_amount' => $order->items->sum('total_price'),
                'lessor_payout' => $order->base_amount - $order->items->sum('discount_amount'),
            ]);
        });
    }
}