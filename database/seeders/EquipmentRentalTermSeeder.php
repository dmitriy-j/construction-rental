<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;

class EquipmentRentalTermSeeder extends Seeder
{
    public function run()
    {
        // Для каждого оборудования создаем одно условие аренды
        Equipment::each(function ($equipment) {
            // Если условия уже существуют - пропускаем
            if ($equipment->rentalTerms()->count() > 0) {
                return;
            }

            // Определяем тип оборудования
            $isTruck = $this->isTruck($equipment);

            // Создаем условие аренды
            EquipmentRentalTerm::create([
                'equipment_id' => $equipment->id,
                'price_per_hour' => $isTruck ? rand(800, 1500) : rand(500, 2000),
                'price_per_km' => $isTruck ? rand(20, 50) : null,
                'min_rental_hours' => $isTruck ? 8 : rand(4, 8),
                'delivery_days' => rand(1, 3),
                'currency' => 'RUB',
                'return_policy' => $isTruck
                    ? 'Минимальное бронирование: 8 часов. Возврат при отмене за 24 часа'
                    : 'Минимальное бронирование: 4 часа. Возврат при отмене за 12 часов',
            ]);
        });
    }

    protected function isTruck(Equipment $equipment): bool
    {
        return str_contains($equipment->title, 'Самосвал') ||
               str_contains($equipment->title, 'Бетононасос');
    }
}
