<?php

namespace Database\Seeders;

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
         $locations = [
            ['city' => 'Москва', 'region' => 'Московская область', 'latitude' => 55.755826, 'longitude' => 37.617300],
            ['city' => 'Санкт-Петербург', 'region' => 'Ленинградская область', 'latitude' => 59.934280, 'longitude' => 30.335098],
            ['city' => 'Екатеринбург', 'region' => 'Свердловская область', 'latitude' => 56.838011, 'longitude' => 60.597465],
            ['city' => 'Новосибирск', 'region' => 'Новосибирская область', 'latitude' => 55.008353, 'longitude' => 82.935732],
            ['city' => 'Казань', 'region' => 'Татарстан', 'latitude' => 55.796127, 'longitude' => 49.106414],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
