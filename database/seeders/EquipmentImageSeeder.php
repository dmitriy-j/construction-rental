<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentImage;
use Illuminate\Database\Seeder;

class EquipmentImageSeeder extends Seeder
{
    public function run()
    {
        // Для каждого оборудования создаем 3-5 изображений
        Equipment::each(function ($equipment) {
            EquipmentImage::factory()
                ->count(rand(3, 5))
                ->create(['equipment_id' => $equipment->id]);

            // Гарантируем что есть хотя бы одно главное изображение
            if (! $equipment->images()->where('is_main', true)->exists()) {
                $equipment->images()->first()->update(['is_main' => true]);
            }
        });
    }
}
