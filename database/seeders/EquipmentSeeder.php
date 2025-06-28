<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run()
    {
        // Создаем демо-запись
        Equipment::create([
            'title' => 'Экскаватор JCB',
            'slug' => 'ekskavator-jcb',
            'description' => 'Мощный экскаватор для земляных работ',
            'company_id' => 1,
            'category_id' => 1,
            'location_id' => 1,
            'brand' => 'JCB',
            'model' => '3CX',
            'year' => 2020,
            'hours_worked' => 500,
            'is_approved' => true,
        ]);

        // Создаем 49 случайных записей через фабрику
        Equipment::factory()->count(49)->create();
    }
}
