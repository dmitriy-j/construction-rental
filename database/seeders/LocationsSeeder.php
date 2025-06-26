<?php

namespace Database\Seeders;

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
        Location::create([
            'city' => 'Москва',
            'region' => 'Московская область',
            'latitude' => 55.755826,
            'longitude' => 37.617300,
        ]);
    }
}
