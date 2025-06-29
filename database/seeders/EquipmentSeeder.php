<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentRentalTerm; // Правильный импорт
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run()
    {
        // Создаем демо-запись
        $equipment = Equipment::create([
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

        // Создаем условия аренды
        EquipmentRentalTerm::create([ // Исправлено здесь
            'equipment_id' => $equipment->id,
            'period' => 'смена',
            'price' => 15000.00,
            'currency' => 'RUB',
            'delivery_price' => 3000.00,
            'delivery_days' => 2,
            'return_policy' => 'Возврат при отмене за 24 часа'
        ]);

        // Для фабричных записей
        Equipment::factory()->count(49)
            ->has(EquipmentRentalTerm::factory()->state([ // Исправлено здесь
                'period' => 'смена',
                'price' => rand(10000, 30000),
                'currency' => 'RUB'
            ]), 'rentalTerms') // Это должно соответствовать имени отношения в модели Equipment
            ->create();
    }
}
