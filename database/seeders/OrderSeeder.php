<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompletionAct;
use App\Models\Contract;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Operator;
use App\Models\Order;
use App\Models\Platform;
use App\Models\RentalCondition;
use App\Models\Waybill;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

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

            if (! $hasOperators) {
                $operatorCount = rand(1, 3);
                for ($i = 0; $i < $operatorCount; $i++) {
                    Operator::create([
                        'company_id' => $equipment->company_id,
                        'equipment_id' => $equipment->id,
                        'full_name' => fake()->lastName().' '.fake()->firstName(),
                        'phone' => fake()->phoneNumber(),
                        'license_number' => 'LN-'.rand(1000, 9999),
                        'qualification' => fake()->randomElement(['Экскаваторщик', 'Крановщик', 'Бульдозерист', 'Погрузчик']),
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    protected function createDocuments(Order $order)
    {
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
                'updated_at' => $now,
            ];
        }

        DB::table('order_items')->insert($items);

        // Создаем путевые листы
        $waybills = [];
        $waybillCount = rand(2, 5);

        for ($i = 0; $i < $waybillCount; $i++) {
            // Генерация значений для путевых листов
            $randomItem = $items[array_rand($items)];
            $equipmentId = $randomItem['equipment_id'];

            $operatorId = null;
            if (! empty(self::$operatorsByEquipment[$equipmentId])) {
                $operatorId = self::$operatorsByEquipment[$equipmentId][array_rand(self::$operatorsByEquipment[$equipmentId])];
            } else {
                $equipment = Equipment::find($equipmentId);
                $operator = Operator::create([
                    'company_id' => $equipment->company_id,
                    'equipment_id' => $equipment->id,
                    'full_name' => fake()->lastName().' '.fake()->firstName(),
                    'phone' => fake()->phoneNumber(),
                    'license_number' => 'LN-'.rand(1000, 9999),
                    'qualification' => fake()->randomElement(['Экскаваторщик', 'Крановщик', 'Бульдозерист', 'Погрузчик']),
                    'is_active' => true,
                ]);
                $operatorId = $operator->id;
                self::$operatorsByEquipment[$equipmentId][] = $operatorId;
            }

            $startDate = Carbon::now()->subDays(rand(1, 30));
            $endDate = (clone $startDate)->addDays(rand(1, 10));

            $waybillNumber = $this->generateUniqueWaybillNumber(); // Генерация уникального номера

            // Генерация departure_time
            $departureTime = $this->faker->dateTimeBetween($startDate, $endDate);

            $waybill = Waybill::create([
                'order_id' => $order->id,
                'equipment_id' => $equipmentId,
                'operator_id' => $operatorId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => $this->faker->randomElement([
                    Waybill::STATUS_FUTURE,
                    Waybill::STATUS_ACTIVE,
                    Waybill::STATUS_COMPLETED,
                ]),
                'notes' => $i === 0 ? 'Путевой лист' : null,
                'created_at' => $now,
                'updated_at' => $now,
                'number' => $waybillNumber,

            ]);

            $waybills[] = $waybill; // Сохраняем путевые листы в массив
        }

        // Вставляем все путевые листы в базу данных
        foreach ($waybills as $waybill) {
            $waybill->save();
        }

        // Для каждого waybill создаем смены
        foreach ($waybills as $waybill) {
            $shifts = [];
            $daysCount = $waybill->start_date->diffInDays($waybill->end_date);

            for ($day = 0; $day <= $daysCount; $day++) {
                $shiftDate = (clone $waybill->start_date)->addDays($day);

                // Инициализация переменных
                $downtimeCause = $this->faker->randomElement(['?', 'lessor', 'force_majeure']);
                $downtimeHours = rand(0, 10);
                $fuelEnd = rand(30, 100);
                $fuelRefilledLiters = rand(0, 50);
                $fuelStart = rand(30, 100);
                $hourlyRate = rand(100, 200);
                $hoursWorked = rand(1, 12);
                $mechanicSignaturePath = '/signatures/mechanic_'.$day.'.png';
                $odometerEnd = rand(5000, 10000);
                $odometerStart = rand(1000, 5000);
                $operatorId = 147; // Пример ID оператора
                $operatorSignaturePath = '/signatures/operator_'.$day.'.png';
                $shiftType = $day % 2 === 0 ? 'day' : 'night';
                $returnTime = null; // Инициализация переменной
                $totalAmount = $this->faker->randomFloat(2, 100, 1000);

                // Пример генерации return_time, если это необходимо
                if ($shiftType === 'day') {
                    $returnTime = $this->faker->dateTimeBetween($departureTime, $endDate);
                } else {
                    $returnTime = $this->faker->dateTimeBetween($departureTime, $endDate);
                }

                // Добавление записи в массив
                $shifts[] = [
                    'downtime_cause' => $downtimeCause,
                    'downtime_hours' => $downtimeHours,
                    'fuel_end' => $fuelEnd,
                    'fuel_refilled_liters' => $fuelRefilledLiters,
                    'fuel_start' => $fuelStart,
                    'hourly_rate' => $hourlyRate,
                    'hours_worked' => $hoursWorked,
                    'mechanic_signature_path' => $mechanicSignaturePath,
                    'odometer_end' => $odometerEnd,
                    'odometer_start' => $odometerStart,
                    'operator_id' => $operatorId,
                    'operator_signature_path' => $operatorSignaturePath,
                    'shift_date' => $shiftDate,
                    'shift_type' => $shiftType,
                    'waybill_id' => $waybill->id,
                    'object_address' => 'Адрес объекта',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'departure_time' => $departureTime, // Добавляем departure_time
                    'return_time' => $returnTime,
                    'total_amount' => $totalAmount,
                ];
            }

            DB::table('waybill_shifts')->insert($shifts);
        }

        // Получаем все завершенные заказы
        $completedOrders = Order::where('status', 'completed')->get();

        foreach ($completedOrders as $order) {
            // Проверяем, есть ли waybill_id
            if ($order->waybill_id) {
                CompletionAct::create([
                    'order_id' => $order->id,
                    'waybill_id' => $order->waybill_id,
                    'act_date' => now(),
                    'total_hours' => rand(10, 100),
                    'total_downtime' => rand(0, 10),
                    'hourly_rate' => $order->hourly_rate ?? 0, // Если hourly_rate есть в модели Order
                    'total_amount' => $order->total_amount,
                    'notes' => $order->notes ?? '', // Если notes есть в модели Order
                    'document_path' => '/completion_acts/act_'.$order->id.'.pdf',
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function generateUniqueWaybillNumber()
    {
        // Генерация уникального номера
        $baseNumber = 'ЭСМ-2-'.date('Ymd');
        $uniqueNumber = $baseNumber;
        $count = 1;

        while (Waybill::where('number', $uniqueNumber)->exists()) {
            $uniqueNumber = $baseNumber.'-'.str_pad($count++, 5, '0', STR_PAD_LEFT);
        }

        return $uniqueNumber;
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
            'status' => 'pending_approval',
        ]);

        // Добавить новые статусы в создание тестовых заказов
        $rejectedOrder = Order::factory()->create([
            'status' => 'rejected',
            'rejection_reason' => 'Техника недоступна',
            'rejected_at' => now(),
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
                'updated_at' => $now,
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
