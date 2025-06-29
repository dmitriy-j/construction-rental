<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Platform;

class OrderSeeder extends Seeder
{
    public function run()
    {
        // Создаем 50 заказов
        Order::factory()->count(50)->create();

        // Специальные тестовые заказы
        $this->createTestOrders();
    }

    protected function createTestOrders()
    {
        $platform = Platform::first(); // Получаем главную платформу

        // Заказ в статусе pending
        Order::factory()->create([
            'platform_id' => $platform->id,
            'status' => 'pending',
            'total_amount' => 15000.00,
        ]);

        // Заказ в статусе confirmed
        Order::factory()->create([
            'platform_id' => $platform->id,
            'status' => 'confirmed',
            'total_amount' => 25000.00,
        ]);

        // Заказ с большим количеством позиций
        $order = Order::factory()->create([
            'platform_id' => $platform->id,
            'status' => 'active',
            'total_amount' => 45000.00,
        ]);

        // Добавляем 5 позиций
        for ($i = 0; $i < 5; $i++) {
            $order->items()->create([
                'equipment_id' => \App\Models\Equipment::inRandomOrder()->first()->id,
                'rental_term_id' => \App\Models\EquipmentRentalTerm::inRandomOrder()->first()->id,
                'quantity' => rand(1, 2),
                'base_price' => rand(1000, 5000),
                'price_per_unit' => rand(500, 2500),
                'platform_fee' => rand(100, 500),
                'discount_amount' => rand(0, 200),
                'total_price' => rand(1500, 6000),
                'period_count' => rand(1, 7),
            ]);
        }
    }
}
