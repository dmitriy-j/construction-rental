<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    private static $lesseeCompanies;

    private static $lessorCompanies;

    private static $users;

    private static $contracts;

    public function definition()
    {
        if (! isset(self::$lesseeCompanies)) {
            self::$lesseeCompanies = Company::where('is_lessee', true)->pluck('id')->all();
            self::$lessorCompanies = Company::where('is_lessor', true)->pluck('id')->all();
            self::$users = User::pluck('id')->all();
            self::$contracts = Contract::pluck('id')->all();
        }

        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = Carbon::parse($startDate)->addDays(rand(1, 14));

        return [
            'platform_id' => Platform::first()->id,
            'lessee_company_id' => $this->faker->randomElement(self::$lesseeCompanies),
            'lessor_company_id' => $this->faker->randomElement(self::$lessorCompanies),
            'contract_id' => $this->faker->randomElement(self::$contracts),
            'user_id' => $this->faker->randomElement(self::$users),
            'status' => $this->faker->randomElement([
                'pending',
                'pending_approval', // новый статус
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'rejected', // новый статус
            ]),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),

            // Инициализация всех полей с суммами
            'total_amount' => $this->faker->numberBetween(10000, 100000),
            'base_amount' => $this->faker->numberBetween(5000, 50000),
            'platform_fee' => $this->faker->numberBetween(500, 5000),
            'prepayment_amount' => $this->faker->numberBetween(0, 10000),
            'discount_amount' => $this->faker->numberBetween(0, 5000),
            'lessor_payout' => $this->faker->numberBetween(5000, 60000),
            'penalty_amount' => $this->faker->numberBetween(0, 2000),
            'service_start_date' => $this->faker->optional()->dateTimeBetween($startDate, $endDate),
            'service_end_date' => $this->faker->optional()->dateTimeBetween($startDate, $endDate),
            'extension_requested' => false,
            'requested_end_date' => null,
            'notes' => $this->faker->optional(0.3)->sentence,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            // Создаем DeliveryNote для каждого OrderItem
            foreach ($order->items as $item) {
                \App\Models\DeliveryNote::factory()->create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'sender_company_id' => $order->lessor_company_id,
                    'receiver_company_id' => $order->lessee_company_id,
                    'delivery_from_id' => $item->delivery_from_id,
                    'delivery_to_id' => $item->delivery_to_id,
                ]);
            }
        });
    }
}
