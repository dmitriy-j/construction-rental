<?php

// Исправленный тест с добавленными импортами
use App\Models\Platform;
use App\Models\Company;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Contract; // Добавлен импорт Contract
use App\Models\DeliveryNote; // Добавлен импорт DeliveryNote
use App\Models\Waybill; // Добавлен импорт Waybill
use App\Services\CompletionActGenerator; // Импорт генератора актов

// 1. Получаем данные
$platform = Platform::first();
$lessor = Company::where('type', 'lessor')->first();
$lessee = Company::where('type', 'lessee')->first();
$user = User::first();
$equipment = Equipment::first();

// 2. Создаем заказ
$order = Order::create([
    'platform_id' => $platform->id,
    'lessee_company_id' => $lessee->id,
    'lessor_company_id' => $lessor->id,
    'user_id' => $user->id,
    'status' => 'confirmed',
    'total_amount' => 15000.00,
    'base_amount' => 12000.00,
    'platform_fee' => 3000.00,
    'start_date' => now()->addDays(5),
    'end_date' => now()->addDays(15),
]);

// 3. Добавляем позиции заказа
$item = OrderItem::create([
    'order_id' => $order->id,
    'equipment_id' => $equipment->id,
    'rental_term_id' => $equipment->rentalTerms->first()->id,
    'quantity' => 1,
    'base_price' => 1000.00,       // Добавлено
    'price_per_unit' => 1000.00,
    'platform_fee' => 0.00,        // Добавлено
    'discount_amount' => 0.00,     // Добавлено
    'total_price' => 1000.00,
    'period_count' => 1            // Добавлено
]);

// 4. Создаем договор
$contract = Contract::create([ // Теперь Contract распознается
    'order_id' => $order->id,
    'number' => 'Д-2023-001',
    'payment_type' => 'postpay',
    'documentation_deadline' => 7,
    'payment_deadline' => 7,
    'penalty_rate' => 50,
]);

// 5. Создаем транспортную накладную
$deliveryNote = DeliveryNote::create([ // Теперь DeliveryNote распознается
    'order_id' => $order->id,
    'delivery_date' => now()->addDays(5),
    'driver_name' => 'Иванов Иван Иванович',
    'receiver_name' => 'Петров Петр Петрович',
]);

// 6. Обновляем даты в заказе
$order->update([
    'service_start_date' => $deliveryNote->delivery_date,
    'contract_date' => $contract->created_at->format('Y-m-d')
]);

// 7. Создаем путевые листы
$waybill1 = Waybill::create([ // Теперь Waybill распознается
    'order_id' => $order->id,
    'equipment_id' => $equipment->id,
    'operator_id' => $user->id,
    'work_date' => now()->addDays(5),
    'hours_worked' => 8.5,
    'downtime_hours' => 1.0,
    'downtime_cause' => 'lessee'
]);

$waybill2 = Waybill::create([
    'order_id' => $order->id,
    'equipment_id' => $equipment->id,
    'operator_id' => $user->id,
    'work_date' => now()->addDays(6),
    'hours_worked' => 10.0,
    'downtime_hours' => 0.0
]);

// 8. Генерируем акт выполненных работ
$actGenerator = new CompletionActGenerator();
$completionAct = CompletionActGenerator::generate($order);

// 9. Проверяем результаты
echo "Order ID: ".$order->id."\n";
echo "Service Period: ".$order->service_start_date->format('d.m.Y')." - ".$completionAct->service_end_date->format('d.m.Y')."\n";
echo "Total Hours: ".$completionAct->total_hours."\n";
echo "Downtime Hours: ".$completionAct->total_downtime."\n";
echo "Penalty Amount: ".$completionAct->penalty_amount."\n";
echo "Total Amount: ".$completionAct->total_amount."\n";
echo "Final Amount: ".$completionAct->final_amount."\n";

// 10. Проверяем связи
echo "\nRelations check:\n";
echo "Order contract: ".($order->contract ? "Exists" : "Missing")."\n";
echo "Order delivery note: ".($order->deliveryNote ? "Exists" : "Missing")."\n";
echo "Order waybills: ".$order->waybills()->count()."\n";
echo "Order completion act: ".($order->completionAct ? "Exists" : "Missing")."\n";
