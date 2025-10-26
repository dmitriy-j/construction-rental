<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
        // Создаем конкретные локации
        $specificLocations = [
            [
                'name' => 'Техническая база',
                'address' => 'Москва, Московская область, ул. Складская, 1',
                'latitude' => 55.755826,
                'longitude' => 37.6173,
                'company_id' => 1,
            ],
            [
                'name' => 'Строительный объект М11 Москва-Санкт-Петербург Этап 1',
                'address' => 'Санкт-Петербург, Ленинградская область, пр. Складской, 5',
                'latitude' => 59.934280,
                'longitude' => 30.335098,
                'company_id' => 2,
            ],
            [
                'name' => 'Строительный объект М11 Москва-Санкт-Петербург Этап 2',
                'address' => 'Санкт-Петербург, Ленинградская область, посёлок Сельцо',
                'latitude' => 59.336602,
                'longitude' => 31.207536,
                'company_id' => 3,
            ],
            [
                'name' => 'Техническая база',
                'address' => 'Москва, Московская область, район Троицк, квартал № 115',
                'latitude' => 55.503883,
                'longitude' => 37.276558,
                'company_id' => 4,
            ],
        ];

        foreach ($specificLocations as $location) {
            Location::create($location);
        }

        // Создаем дополнительные случайные локации
        Location::factory()->count(50)->create();
    }
}
