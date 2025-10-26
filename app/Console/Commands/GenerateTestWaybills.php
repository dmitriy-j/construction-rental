<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Waybill;
use App\Models\WaybillShift;
use Illuminate\Console\Command;

class GenerateTestWaybills extends Command
{
    protected $signature = 'test:waybills {days=10} {--shifts=10}';

    protected $description = 'Generate test waybills for testing';

    public function handle()
    {
        $days = (int) $this->argument('days');
        $shifts = (int) $this->option('shifts');
        $startDate = now()->subDays($days);

        // Используем существующие данные
        $lesseeCompany = Company::find(6);
        $lessorCompany = Company::find(10);
        $user = User::find(4);
        $equipment = Equipment::find(55);
        $equipmentLocation = Location::find(59);
        $constructionLocation = Location::find(43);

        if (! $equipment || ! $equipmentLocation || ! $constructionLocation || ! $lesseeCompany || ! $lessorCompany || ! $user) {
            $this->error('Required entities not found!');
            $this->line('Missing:');
            $this->line('- Equipment: '.($equipment ? 'Found' : 'Not found'));
            $this->line('- Equipment Location: '.($equipmentLocation ? 'Found' : 'Not found'));
            $this->line('- Construction Location: '.($constructionLocation ? 'Found' : 'Not found'));
            $this->line('- Lessee Company: '.($lesseeCompany ? 'Found' : 'Not found'));
            $this->line('- Lessor Company: '.($lessorCompany ? 'Found' : 'Not found'));
            $this->line('- User: '.($user ? 'Found' : 'Not found'));

            return;
        }

        // Создаем тестовый заказ
        $order = Order::create([
            'lessee_company_id' => $lesseeCompany->id,
            'lessor_company_id' => $lessorCompany->id,
            'user_id' => $user->id,
            'status' => Order::STATUS_ACTIVE,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($days),
            'total_amount' => $days * 8000,
            'rental_condition_id' => 1,
            'shift_hours' => 8,
            'shifts_per_day' => 1,
            'delivery_type' => Order::DELIVERY_DELIVERY,
            'delivery_from_id' => $equipmentLocation->id,
            'delivery_to_id' => $constructionLocation->id,
        ]);

        // Создаем позицию заказа
        $item = OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'rental_term_id' => 1,
            'quantity' => 1,
            'base_price' => 1000,
            'price_per_unit' => 1000,
            'total_price' => $days * 1000,
            'period_count' => $days,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($days),
            'delivery_from_id' => $equipmentLocation->id,
            'delivery_to_id' => $constructionLocation->id,
            'lessor_company_id' => $lessorCompany->id,

            // Добавляем значения для новых полей
            'fixed_lessor_price' => 800, // Ставка для арендодателя
            'fixed_customer_price' => 1000, // Ставка для арендатора
            'distance_km' => 50, // Примерное расстояние
        ]);

        // Используем существующего оператора или создаем нового
        $operator = Operator::firstOrCreate(
            ['license_number' => 'TEST123'],
            [
                'full_name' => 'Test Operator',
                'company_id' => $lessorCompany->id,
                'is_active' => true,
                'equipment_id' => $equipment->id,
            ]
        );

        // Создаем путевой лист
        $waybill = Waybill::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'equipment_id' => $equipment->id,
            'operator_id' => $operator->id,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays(min($days, 10) - 1),
            'status' => Waybill::STATUS_ACTIVE,
            'hourly_rate' => 1000,
            'lessor_hourly_rate' => 800,
            'license_plate' => 'А123ВС77',
        ]);

        // Создаем смены
        for ($i = 0; $i < $shifts; $i++) {
            $shiftDate = $startDate->copy()->addDays($i);

            WaybillShift::create([
                'waybill_id' => $waybill->id,
                'shift_date' => $shiftDate,
                'operator_id' => $operator->id,
                'hours_worked' => 8,
                'total_amount' => 6400,
                'odometer_start' => $i * 100,
                'odometer_end' => $i * 100 + 80,
                'fuel_start' => 50,
                'fuel_end' => 30,
                'fuel_refilled_liters' => 20,
                'work_start_time' => '08:00',
                'work_end_time' => '16:00',
                'object_name' => 'Строительная площадка #'.($i + 1),
                'object_address' => $constructionLocation->address,
            ]);
        }

        $this->info('Successfully created test waybill!');
        $this->info("Order ID: {$order->id}");
        $this->info("Waybill ID: {$waybill->id}");
        $this->info('Access URL: '.route('lessor.waybills.show', $waybill));
    }
}
