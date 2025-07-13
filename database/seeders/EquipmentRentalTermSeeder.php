<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;

class EquipmentRentalTermSeeder extends Seeder
{
    public function run()
    {
        $periods = ['час', 'смена', 'сутки', 'месяц'];

        Equipment::each(function ($equipment) use ($periods) {
            // Если нет условий аренды - создаем все периоды
            if ($equipment->rentalTerms()->count() === 0) {
                foreach ($periods as $period) {
                    $this->createRentalTerm($equipment, $period);
                }
            }
            // Иначе добавляем недостающие периоды
            else {
                $existingPeriods = $equipment->rentalTerms()->pluck('period')->toArray();
                $availablePeriods = array_diff($periods, $existingPeriods);

                foreach ($availablePeriods as $period) {
                    $this->createRentalTerm($equipment, $period);
                }
            }
        });
    }

    protected function createRentalTerm($equipment, $period)
    {
        $prices = [
            'час' => rand(500, 2000),
            'смена' => rand(5000, 15000),
            'сутки' => rand(10000, 30000),
            'месяц' => rand(100000, 500000),
        ];

        $returnPolicies = [
            'час' => 'Минимальное бронирование: 4 часа',
            'смена' => 'Возврат при отмене за 24 часа',
            'сутки' => 'Возврат при отмене за 48 часов',
            'месяц' => 'Возврат при отмене за 7 дней',
        ];

        EquipmentRentalTerm::create([
            'equipment_id' => $equipment->id,
            'period' => $period,
            'price' => $prices[$period] ?? rand(1000, 5000),
            'currency' => 'RUB',
            'delivery_price' => rand(500, 5000),
            'delivery_days' => rand(1, 3),
            'return_policy' => $returnPolicies[$period] ?? 'Стандартные условия возврата',
        ]);
    }
}
