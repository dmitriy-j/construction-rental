<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Operator;
use App\Models\Order;
use App\Models\Waybill;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaybillFactory extends Factory
{
    protected $model = Waybill::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = (clone $startDate)->modify('+'.rand(1, 10).' days');

        return [
            'order_id' => Order::factory(),
            'equipment_id' => Equipment::factory(),
            'operator_id' => Operator::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement([
                Waybill::STATUS_FUTURE,
                Waybill::STATUS_ACTIVE,
                Waybill::STATUS_COMPLETED,
            ]),
            'notes' => $this->faker->optional()->sentence,
            'number' => $this->generateUniqueWaybillNumber(), // Генерация уникального номера
        ];
    }

    private function generateUniqueWaybillNumber()
    {
        $baseNumber = 'ЭСМ-2-'.date('Ymd');
        $count = 1;
        $uniqueNumber = $baseNumber;

        // Генерация уникального номера
        while (Waybill::where('number', $uniqueNumber)->exists()) {
            $uniqueNumber = $baseNumber.'-'.str_pad($count++, 5, '0', STR_PAD_LEFT);
        }

        return $uniqueNumber;
    }
}
