<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Waybill;
use App\Models\CompletionAct;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\RentalCondition;
use App\Models\EquipmentRentalTerm;
use App\Models\Operator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    // Статические кэши для данных
    private static $equipmentIds;
    private static $rentalConditions;
    private static $rentalTermsCache = [];
    private static $operatorsByEquipment = [];
    private static $companies;
    private static $contracts;

    public function run()
    {
        // Предзагрузка данных
        self::$equipmentIds = Equipment::pluck('id')->all();
        self::$rentalConditions = RentalCondition::pluck('id')->all();
        self::$companies = Company::where('is_lessor', true)->get();
        self::$contracts = Contract::all();

        // Гарантируем наличие операторов для всего оборудования
        $this->ensureOperatorsForAllEquipment();

        // Предзагрузка операторов по оборудованию
        $operators = Operator::where('is_active', true)->get();
        foreach ($operators as $operator) {
            if ($operator->equipment_id) {
                self::$operatorsByEquipment[$operator->equipment_id][] = $operator->id;
            }
        }

        // Предзагрузка rental_term_id для оборудования
        foreach (self::$equipmentIds as $equipmentId) {
            $term = Equipment::find($equipmentId)->rentalTerms()->first();
            if ($term) {
                self::$rentalTermsCache[$equipmentId] = $term->id;
            } else {
                $term = EquipmentRentalTerm::factory()->create(['equipment_id' => $equipmentId]);
                self::$rentalTermsCache[$equipmentId] = $term->id;
            }
        }

        $this->command->info('Creating orders...');
        $progressBar = $this->command->getOutput()->createProgressBar(50);

        for ($i = 0; $i < 50; $i++) {
            $order = Order::factory()->create();
            $this->createDocuments($order);
            $progressBar->advance();
            unset($order);
            gc_collect_cycles();
        }

        $progressBar->finish();
        $this->command->info("\nOrders created");
        $this->createTestOrders();
    }

    protected function ensureOperatorsForAllEquipment()
    {
        $equipments = Equipment::all();

        foreach ($equipments as $equipment) {
            $hasOperators = Operator::where('equipment_id', $equipment->id)
                ->where('is_active', true)
                ->exists();

            if (!$hasOperators) {
                $operatorCount = rand(1, 3);
                for ($i = 0; $i < $operatorCount; $i++) {
                    Operator::create([
                        'company_id' => $equipment->company_id,
                        'equipment_id' => $equipment->id,
                        'full_name' => fake()->lastName() . ' ' . fake()->firstName(),
                        'phone' => fake()->phoneNumber(),
                        'license_number' => 'LN-' . rand(1000, 9999),
                        'qualification' => fake()->randomElement(['Экскаваторщик', 'Крановщик', 'Бульдозерист', 'Погрузчик']),
                        'is_active' => true
                    ]);
                }
            }
        }
    }

    protected function createDocuments(Order $order)
    {
        // Удалено создание контракта - теперь контракты создаются отдельно

        // Создаем элементы заказа
        $items = [];
        $itemsCount = rand(1, 3);
        $now = now();

        for ($i = 0; $i < $itemsCount; $i++) {
            $equipmentId = self::$equipmentIds[array_rand(self::$equipmentIds)];
            $rentalConditionId = self::$rentalConditions[array_rand(self::$rentalConditions)];

            $quantity = rand(1, 3);
            $basePrice = rand(100, 500);
            $pricePerUnit = rand(500, 1000);
            $platformFee = rand(10, 50);
            $discountAmount = rand(0, 20);
            $totalPrice = ($pricePerUnit * $quantity) - $discountAmount;

            $items[] = [
                'order_id' => $order->id,
                'equipment_id' => $equipmentId,
                'rental_term_id' => self::$rentalTermsCache[$equipmentId],
                'rental_condition_id' => $rentalConditionId,
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'price_per_unit' => $pricePerUnit,
                'platform_fee' => $platformFee,
                'discount_amount' => $discountAmount,
                'total_price' => $totalPrice,
                'period_count' => rand(1, 7),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        DB::table('order_items')->insert($items);

        // Создаем путевые листы
        $waybills = [];
        $waybillCount = rand(2, 5);

        for ($i = 0; $i < $waybillCount; $i++) {
            $randomItem = $items[array_rand($items)];
            $equipmentId = $randomItem['equipment_id'];

            $operatorId = null;
            if (!empty(self::$operatorsByEquipment[$equipmentId])) {
                $operatorId = self::$operatorsByEquipment[$equipmentId][array_rand(self::$operatorsByEquipment[$equipmentId])];
            } else {
                $equipment = Equipment::find($equipmentId);
                $operator = Operator::create([
                    'company_id' => $equipment->company_id,
                    'equipment_id' => $equipmentId,
                    'full_name' => fake()->lastName() . ' ' . fake()->firstName(),
                    'phone' => fake()->phoneNumber(),
                    'license_number' => 'LN-' . rand(1000, 9999),
                    'qualification' => fake()->randomElement(['Экскаваторщик', 'Крановщик', 'Бульдозерист', 'Погрузчик']),
                    'is_active' => true
                ]);
                $operatorId = $operator->id;
                self::$operatorsByEquipment[$equipmentId][] = $operatorId;
            }

            $waybills[] = [
                'order_id' => $order->id,
                'equipment_id' => $equipmentId,
                'operator_id' => $operatorId,
                'work_date' => Carbon::now()->subDays(rand(1, 30)),
                'hours_worked' => rand(1, 10),
                'downtime_hours' => rand(0, 5),
                'downtime_cause' => rand(0, 1) ? ['lessee', 'lessor', 'force_majeure'][rand(0,2)] : null,
                'operator_signature_path' => '/signatures/operator_'.$order->id.'_'.$i.'.png',
                'customer_signature_path' => '/signatures/customer_'.$order->id.'_'.$i.'.png',
                'notes' => $i === 0 ? 'Путевой лист' : null,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        DB::table('waybills')->insert($waybills);

        // Для завершенных заказов создаем акт
        if ($order->status === 'completed') {
            CompletionAct::create([
                'order_id' => $order->id,
                'act_date' => now(),
                'service_start_date' => $order->service_start_date ?? $order->start_date,
                'service_end_date' => $order->service_end_date ?? $order->end_date,
                'total_hours' => rand(10, 100),
                'total_downtime' => rand(0, 10),
                'penalty_amount' => 0,
                'total_amount' => $order->total_amount,
                'prepayment_amount' => $order->prepayment_amount,
                'final_amount' => $order->total_amount - $order->prepayment_amount,
                'act_file_path' => '/completion_acts/act_'.$order->id.'.pdf',
                'status' => 'draft'
            ]);
        }
    }

    protected function createTestOrders()
    {
        $this->command->info('Creating test orders...');

        $platform = Platform::first();
        $lessee = Company::where('is_lessee', true)->first();
        $lessor = Company::where('is_lessor', true)->first();
        $contract = self::$contracts->first();

        // Заказ в статусе pending
        $pendingOrder = Order::factory()->create([
            'platform_id' => $platform->id,
            'lessee_company_id' => $lessee->id,
            'lessor_company_id' => $lessor->id,
            'contract_id' => $contract->id,
            'status' => 'pending',
        ]);
        $this->createDocuments($pendingOrder);

        // Заказ в статусе confirmed
        $confirmedOrder = Order::factory()->create([
            'platform_id' => $platform->id,
            'lessee_company_id' => $lessee->id,
            'lessor_company_id' => $lessor->id,
            'contract_id' => $contract->id,
            'status' => 'confirmed',
        ]);
        $this->createDocuments($confirmedOrder);

        // Заказ с большим количеством позиций
        $order = Order::factory()->create([
            'platform_id' => $platform->id,
            'lessee_company_id' => $lessee->id,
            'lessor_company_id' => $lessor->id,
            'contract_id' => $contract->id,
            'status' => 'active',
        ]);
        $this->createDocuments($order);

        // Добавляем 5 позиций
        $items = [];
        $now = now();

        for ($i = 0; $i < 5; $i++) {
            $equipmentId = self::$equipmentIds[array_rand(self::$equipmentIds)];
            $rentalConditionId = self::$rentalConditions[array_rand(self::$rentalConditions)];

            $quantity = rand(1, 2);
            $basePrice = rand(100, 500);
            $pricePerUnit = rand(500, 1000);
            $platformFee = rand(10, 50);
            $discountAmount = rand(0, 20);
            $totalPrice = ($pricePerUnit * $quantity) - $discountAmount;

            $items[] = [
                'order_id' => $order->id,
                'equipment_id' => $equipmentId,
                'rental_term_id' => self::$rentalTermsCache[$equipmentId],
                'rental_condition_id' => $rentalConditionId,
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'price_per_unit' => $pricePerUnit,
                'platform_fee' => $platformFee,
                'discount_amount' => $discountAmount,
                'total_price' => $totalPrice,
                'period_count' => rand(1, 7),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        DB::table('order_items')->insert($items);

        // Пересчитываем итоги
        $baseAmount = collect($items)->sum('base_price');
        $platformFee = collect($items)->sum('platform_fee');
        $discountAmount = collect($items)->sum('discount_amount');
        $deliveryCost = $order->deliveryNote ? $order->deliveryNote->calculated_cost : 0;
        $totalAmount = collect($items)->sum('total_price') + $deliveryCost;

        $order->update([
            'base_amount' => $baseAmount,
            'platform_fee' => $platformFee,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);
    }
}
