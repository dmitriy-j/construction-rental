<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\RentalTerm;
use Illuminate\Database\Seeder;

class RentalTermSeeder extends Seeder
{
    public function run()
    {
        // Для каждого оборудования создаем 2-4 условия аренды
        Equipment::each(function ($equipment) {
            RentalTerm::factory()
                ->count(rand(2, 4))
                ->create(['equipment_id' => $equipment->id]);
        });
    }
}
