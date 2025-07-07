<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\CompletionAct;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Waybill;

class OrderDocumentsSeeder extends Seeder
{
    public function run()
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            // Основные документы
            Contract::factory()->create(['order_id' => $order->id]);
            DeliveryNote::factory()->create(['order_id' => $order->id]);
            
            // Путевые листы (3-7 на заказ)
            Waybill::factory()->count(rand(3, 7))->create(['order_id' => $order->id]);
            
            // Акт выполненных работ (только для завершенных)
            if ($order->status === 'completed') {
                CompletionAct::factory()->create(['order_id' => $order->id]);
            }
        }
    }
}