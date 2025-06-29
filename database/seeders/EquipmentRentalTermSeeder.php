<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm; // Исправленный импорт

class EquipmentRentalTermSeeder extends Seeder
{
    public function run()
    {
        // Для каждого оборудования создаем 2-4 условия аренды
        Equipment::each(function ($equipment) {
            EquipmentRentalTerm::factory() // Исправлено здесь
                ->count(rand(2, 4))
                ->create(['equipment_id' => $equipment->id]);
        });
    }
}
